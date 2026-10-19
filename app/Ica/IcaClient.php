<?php

namespace App\Ica;

use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * ICA's shopping lists through the API ICA's own app uses. It is unofficial
 * (not offered by ICA, may change without notice), so the family's own list
 * never depends on it. Ported from ica-cli/ha-ica-todo.
 *
 * Login is ICA's OAuth with PKCE and the personal ID and password, once. The
 * password is only used during login and never kept; afterwards the refresh
 * token keeps the connection going.
 */
class IcaClient
{
    private const AUTH = 'https://ims.icagruppen.se';

    private const API = 'https://apimgw-pub.ica.se/sverige/digx/mobile/shoppinglistservice/v1';

    private const REDIRECT = 'icacurity://app';

    private const ACR = 'urn:se:curity:authentication:html-form:IcaCustomers';

    /**
     * Whether the server has what is needed to talk to ICA at all.
     */
    public static function available(): bool
    {
        return filled(config('services.ica.client_id')) && filled(config('services.ica.client_secret'));
    }

    /**
     * Log in with the personal ID and password.
     *
     * @return array{client: array<string, mixed>, token: array<string, mixed>}
     *
     * @throws IcaException When ICA says no or answers in an unexpected way.
     */
    public function login(string $personalId, string $password): array
    {
        $jar = new CookieJar;
        $http = fn (): PendingRequest => Http::withOptions(['cookies' => $jar])->timeout(30);

        return $this->guard(function () use ($http, $personalId, $password): array {
            // 1. A client of our own, registered the way the app does it.
            $registration = $http()->asForm()->post(self::AUTH.'/oauth/v2/token', [
                // The registration client of ICA's app, from the server's .env (services.ica).
                'client_id' => (string) config('services.ica.client_id'),
                'client_secret' => (string) config('services.ica.client_secret'),
                'grant_type' => 'client_credentials',
                'scope' => 'dcr',
                'response_type' => 'token',
            ])->throw()->json('access_token');

            $client = (array) $http()->withToken((string) $registration)
                ->post(self::AUTH.'/register', ['software_id' => 'dcr-ica-app-template'])
                ->throw()
                ->json();

            // 2. Start the login, with PKCE.
            $verifier = Str::random(64);
            $challenge = rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=');

            $authorize = $http()->withoutRedirecting()->get(self::AUTH.'/oauth/v2/authorize', [
                'client_id' => $client['client_id'] ?? '',
                'scope' => $client['scope'] ?? '',
                'redirect_uri' => self::REDIRECT,
                'response_type' => 'code',
                'code_challenge' => $challenge,
                'code_challenge_method' => 'S256',
                'prompt' => 'login',
                'acr' => self::ACR,
            ]);
            $location = $authorize->header('Location');
            $state = $this->match('/[?&]state=(\w+)/', $location);
            $http()->get(str_starts_with($location, 'http') ? $location : self::AUTH.$location);

            // 3. The personal ID and password: used here only, never kept.
            $form = $http()->asForm()->post(self::AUTH.'/authn/authenticate/IcaCustomers', [
                'userName' => $personalId,
                'password' => $password,
            ]);

            if ($form->status() === 400) {
                throw new IcaException('ICA godkände inte personnumret eller lösenordet.');
            }

            $form->throw();
            $formToken = $this->match('/name="token" value="(\w+)/', $form->body());

            // 4. The code for tokens.
            $done = $http()->withoutRedirecting()->asForm()
                ->post(self::AUTH.'/oauth/v2/authorize?'.http_build_query([
                    'client_id' => $client['client_id'] ?? '',
                    'forceAuthN' => 'true',
                    'acr' => self::ACR,
                ]), ['token' => $formToken, 'state' => $state]);
            $code = $this->match('/[?&]code=(\w+)/', $done->header('Location'));

            $token = (array) $http()->asForm()->post(self::AUTH.'/oauth/v2/token', [
                'code' => $code,
                'client_id' => $client['client_id'] ?? '',
                'client_secret' => $client['client_secret'] ?? '',
                'grant_type' => 'authorization_code',
                'scope' => $client['scope'] ?? '',
                'response_type' => 'token',
                'code_verifier' => $verifier,
                'redirect_uri' => self::REDIRECT,
            ])->throw()->json();

            return ['client' => $client, 'token' => $token];
        });
    }

    /**
     * New tokens from the refresh token, without the password.
     *
     * @param  array<string, mixed>  $client
     * @param  array<string, mixed>  $token
     * @return array<string, mixed>
     */
    public function refresh(array $client, array $token): array
    {
        return $this->guard(fn (): array => [...$token, ...(array) Http::timeout(30)
            ->withBasicAuth((string) ($client['client_id'] ?? ''), (string) ($client['client_secret'] ?? ''))
            ->asForm()
            ->post(self::AUTH.'/oauth/v2/token', [
                'grant_type' => 'refresh_token',
                'refresh_token' => $token['refresh_token'] ?? '',
            ])
            ->throw()
            ->json()]);
    }

    /**
     * The family's ICA shopping lists.
     *
     * @return list<array{id: string, title: string}>
     */
    public function lists(string $accessToken): array
    {
        $data = (array) $this->guard(fn (): mixed => Http::timeout(30)->withToken($accessToken)
            ->get(self::API.'/shoppinglists')
            ->throw()
            ->json());

        // {"shoppingLists": [...]}, or just the lists.
        if (isset($data['shoppingLists']) && is_array($data['shoppingLists'])) {
            $data = $data['shoppingLists'];
        }

        $lists = [];

        foreach ($data as $list) {
            if (is_array($list) && is_string($list['offlineId'] ?? null)) {
                $lists[] = ['id' => $list['offlineId'], 'title' => is_string($list['title'] ?? null) ? $list['title'] : 'Inköpslista'];
            }
        }

        return $lists;
    }

    /**
     * Adds a row to an ICA list, and returns the row's id there.
     */
    public function add(string $accessToken, string $listId, string $name): string
    {
        $rowId = Str::upper((string) Str::uuid());

        // As ICA's app sends it: a negative sourceId is free text.
        $this->sync($accessToken, $listId, ['createdRows' => [[
            'offlineId' => $rowId,
            'productName' => Str::limit($name, 100, ''),
            'sourceId' => -random_int(1_000_000, 1_000_000_000),
            'isStrikedOver' => false,
            'recipes' => [],
        ]]]);

        return $rowId;
    }

    /**
     * Ticks a row off in ICA's list, or back on.
     */
    public function strike(string $accessToken, string $listId, string $rowId, string $name, bool $struck): void
    {
        $this->sync($accessToken, $listId, ['changedRows' => [[
            'offlineId' => $rowId,
            'productName' => $name,
            'isStrikedOver' => $struck,
            'latestChange' => now()->utc()->format('Y-m-d\TH:i:s\Z'),
        ]]]);
    }

    public function delete(string $accessToken, string $listId, string $rowId): void
    {
        $this->sync($accessToken, $listId, ['deletedRows' => [$rowId]]);
    }

    /**
     * @param  array<string, mixed>  $changes
     */
    private function sync(string $accessToken, string $listId, array $changes): void
    {
        $this->guard(fn (): Response => Http::timeout(30)->withToken($accessToken)
            ->post(self::API.'/shoppinglists/'.rawurlencode($listId).'/sync', $changes)
            ->throw());
    }

    private function match(string $pattern, string $text): string
    {
        if (preg_match($pattern, $text, $match) !== 1) {
            throw new IcaException('ICA svarade på ett oväntat sätt. Inloggningen kan ha ändrats hos ICA.');
        }

        return $match[1];
    }

    /**
     * Any failure talking to ICA as an IcaException, with a calm message.
     *
     * @template T
     *
     * @param  callable(): T  $call
     * @return T
     */
    private function guard(callable $call): mixed
    {
        try {
            return $call();
        } catch (IcaException $exception) {
            throw $exception;
        } catch (RequestException $exception) {
            $status = $exception->response->status();

            throw new IcaException(match ($status) {
                401 => 'ICA vill att du loggar in igen.',
                451 => 'ICA svarar bara anrop från svenska adresser.',
                default => 'ICA svarade inte som väntat ('.$status.').',
            }, $status, $exception);
        } catch (ConnectionException $exception) {
            throw new IcaException('ICA svarar inte just nu.', previous: $exception);
        }
    }
}
