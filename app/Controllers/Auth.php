<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\Shield\Authentication\Authenticators\Session;
use CodeIgniter\Shield\Exceptions\ValidationException;
use CodeIgniter\Shield\Models\UserModel;
use CodeIgniter\Shield\Validation\ValidationRules;

class Auth extends Controller
{
    public function __construct()
    {
        helper('auth');
    }

    /**
     * Shows the login form.
     */
    public function login()
    {
        if (auth()->loggedIn()) {
            return redirect()->to('/beranda');
        }

        return view('auth/login', [
            'title' => 'Masuk - LifeQuest',
        ]);
    }

    /**
     * Handles the login request.
     *
     * Uses Shield's official Session authenticator flow. Credentials are
     * validated and looked up through auth_identities (never users.email).
     */
    public function loginProcess()
    {
        $rules = (new ValidationRules())->getLoginRules();

        $messages = [
            'email' => [
                'required'    => 'Email wajib diisi.',
                'valid_email' => 'Format email tidak valid.',
            ],
            'password' => [
                'required' => 'Kata sandi wajib diisi.',
            ],
        ];

        if (! $this->validateData($this->request->getPost(), $rules, $messages, config('Auth')->DBGroup)) {
            return redirect()->to('/masuk')
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        /** @var array<string, string> $credentials */
        $credentials = $this->request->getPost(config('Auth')->validFields) ?? [];
        $credentials = array_filter($credentials);
        $credentials['password'] = $this->request->getPost('password');

        $remember = (bool) $this->request->getPost('remember');

        /** @var Session $authenticator */
        $authenticator = auth('session')->getAuthenticator();

        $result = $authenticator->remember($remember)->attempt($credentials);

        if (! $result->isOK()) {
            return redirect()->to('/masuk')
                ->withInput()
                ->with('error', 'Email atau kata sandi yang Anda masukkan salah.');
        }

        return redirect()->to('/beranda')->withCookies();
    }

    /**
     * Shows the registration form.
     */
    public function register()
    {
        if (auth()->loggedIn()) {
            return redirect()->to('/beranda');
        }

        return view('auth/register', [
            'title' => 'Daftar - LifeQuest',
        ]);
    }

    /**
     * Handles the registration request.
     *
     * Mirrors Shield's official RegisterController: create a User entity,
     * persist it (which creates the email/password identity via the model
     * callback), add the default group, activate, then complete the login.
     */
    public function registerProcess()
    {
        if (! setting('Auth.allowRegistration')) {
            return redirect()->to('/daftar')->with('error', lang('Auth.registerDisabled'));
        }

        $rules = (new ValidationRules())->getRegistrationRules();

        $messages = [
            'username' => [
                'required'    => 'Username wajib diisi.',
                'min_length'  => 'Username minimal 3 karakter.',
                'max_length'  => 'Username maksimal 30 karakter.',
                'regex_match' => 'Username hanya boleh berisi huruf, angka, dan titik.',
                'is_unique'   => 'Username sudah digunakan.',
            ],
            'email' => [
                'required'    => 'Email wajib diisi.',
                'valid_email' => 'Format email tidak valid.',
                'is_unique'   => 'Email sudah terdaftar.',
            ],
            'password' => [
                'required'        => 'Kata sandi wajib diisi.',
                'strong_password' => 'Kata sandi terlalu lemah. Gunakan minimal 8 karakter dengan huruf besar, huruf kecil, angka, dan simbol.',
            ],
            'password_confirm' => [
                'required' => 'Konfirmasi kata sandi wajib diisi.',
                'matches'  => 'Konfirmasi kata sandi tidak cocok.',
            ],
        ];

        if (! $this->validateData($this->request->getPost(), $rules, $messages, config('Auth')->DBGroup)) {
            return redirect()->to('/daftar')
                ->withInput()
                ->with('errors', $this->localizeRegistrationErrors($this->validator->getErrors()));
        }

        /** @var UserModel $users */
        $users = auth()->getProvider();

        $allowedPostFields = array_keys($rules);
        $user              = $users->createNewUser($this->request->getPost($allowedPostFields));

        try {
            $users->save($user);
        } catch (ValidationException $e) {
            return redirect()->to('/daftar')
                ->withInput()
                ->with('errors', $this->localizeRegistrationErrors($users->errors()));
        } catch (\Throwable $e) {
            log_message('error', 'Registration failed: {message}', ['message' => $e->getMessage()]);

            return redirect()->to('/daftar')
                ->withInput()
                ->with('error', 'Pendaftaran gagal. Silakan coba lagi.');
        }

        // Reload the complete User entity (with ID and identities).
        $user = $users->findById($users->getInsertID());

        // Assign the default Shield group.
        $users->addToDefaultGroup($user);

        /** @var Session $authenticator */
        $authenticator = auth('session')->getAuthenticator();

        $authenticator->startLogin($user);
        $user->activate();
        $authenticator->completeLogin($user);

        return redirect()->to('/beranda')
            ->with('success', 'Selamat datang di LifeQuest!');
    }

    /**
     * Translates Shield's strong-password reasons to Indonesian.
     *
     * Shield's `strong_password` rule injects its own error text, bypassing
     * the custom validation messages. This keeps the registration form fully
     * in Indonesian without modifying vendor files.
     *
     * @param array<string, string> $errors
     *
     * @return array<string, string>
     */
    private function localizeRegistrationErrors(array $errors): array
    {
        $translations = [
            'Passwords must be at least'                => 'Kata sandi minimal 8 karakter.',
            'Password must not be a common password.'   => 'Kata sandi tidak boleh menggunakan kata sandi umum.',
            'Passwords cannot contain re-hashed personal information.' => 'Kata sandi tidak boleh berisi informasi pribadi Anda.',
            'Password is too similar to the username.'  => 'Kata sandi terlalu mirip dengan username.',
            'A Password is required.'                   => 'Kata sandi wajib diisi.',
            'Password cannot exceed'                    => 'Kata sandi terlalu panjang.',
        ];

        foreach ($errors as $field => $message) {
            $clean = preg_replace('/^' . preg_quote((string) $field, '/') . ':\s*/i', '', $message) ?? $message;

            foreach ($translations as $needle => $translated) {
                if (str_contains($clean, $needle)) {
                    $errors[$field] = $translated;
                    break;
                }
            }
        }

        return $errors;
    }

    /**
     * Logs the current user out.
     */
    public function logout()
    {
        auth()->logout();

        return redirect()->to('/masuk')->with('success', 'Anda telah keluar.');
    }

    /**
     * Shows the password recovery notice.
     *
     * Password recovery is intentionally unavailable until the mail
     * transport is configured. No reset request is accepted.
     */
    public function forgotPassword()
    {
        return view('auth/forgot_password', [
            'title' => 'Lupa Kata Sandi - LifeQuest',
        ]);
    }
}
