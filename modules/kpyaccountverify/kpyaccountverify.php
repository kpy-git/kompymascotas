<?php

use PrestaShop\Module\KpyAccountVerify\Install\Installer;
use PrestaShop\PrestaShop\Core\Module\WidgetInterface;

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

class KpyAccountVerify extends Module implements WidgetInterface
{
    public function __construct()
    {
        $this->name = 'kpyaccountverify';
        $this->tab = 'front_office_features';
        $this->version = '1.0.0';
        $this->author = 'KPY Team';

        $this->ps_versions_compliancy = [
            'min' => '8.2',
            'max' => _PS_VERSION_,
        ];

        parent::__construct();

        $this->displayName = $this->trans('Kpy Account Verify', [], 'Modules.Kpyaccountverify.Admin');
        $this->description = $this->trans('Handler account verify.', [], 'Modules.Kpyaccountverify.Admin');

        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.Kpyaccountverify.Admin');

    }

    /**
     * @return bool
     */
    public function install(): bool
    {
        if (!parent::install()) {
            return false;
        }

        $installer = new Installer();

        return $installer->install($this);
    }

    /**
     * @return bool
     */
    public function uninstall(): bool
    {
        if (!parent::uninstall()) {
            return false;
        }

        $installer = new Installer();

        return $installer->uninstall($this);
    }

    public function hookActionCustomerAccountAdd(array $params): void
    {
        /** @var Customer $newCustomer */
        $newCustomer = $params['newCustomer'];

        $token = $this->generateToken($newCustomer->email);
        $this->saveToken($newCustomer, $token);

        $this->sendWelcomeEmail($newCustomer, $token);
    }

    public function generateToken(string $email): string
    {
        return sha1($email . uniqid('', true));
    }

    public function saveToken(Customer $customer, string $token, bool $verifyToken = true): bool
    {
        $tokenColumnName = $verifyToken ? 'token' : 'resend_token';
        if ((int)Db::getInstance()->getValue("SELECT 1 FROM " . _DB_PREFIX_ . "kpy_account_verify WHERE id_customer = " . $customer->id)) {
            return Db::getInstance()->update("kpy_account_verify", [
                $tokenColumnName => $token,
                'date_create' => date(DATE_ATOM),
            ], "id_customer = " . $customer->id);
        }

        return Db::getInstance()->insert('kpy_account_verify', [
            'id_customer' => $customer->id,
            'email' => $customer->email,
            $tokenColumnName => $token,
            'date_create' => date(DATE_ATOM),
        ]);
    }

    public function sendWelcomeEmail(Customer $customer, string $token, string $subject = ''): bool
    {
        $vars = [
            '{firstname}' => $customer->firstname,
            '{lastname}' => $customer->lastname,
            '{email}' => $customer->email,
            '{verify_url}' => $this->context->link->getModuleLink($this->name, 'verify', [
                'i' => $customer->id,
                'v' => $token,
            ]),
        ];

        return Mail::Send(
            $this->context->language->id,
            'account',
            $subject ?: $this->trans('Congrats!!', [], 'Modules.Kpyaccountverify.Emails'),
            $vars,
            $customer->email,
            $customer->firstname . ' ' . $customer->lastname
        );
    }

    public function getTokenByCustomer(int $idCustomer): string
    {
        return Db::getInstance()->getValue(
            "SELECT token FROM " . _DB_PREFIX_ . "kpy_account_verify WHERE id_customer = {$idCustomer}"
        ) ?: '';
    }

    public function isUsingNewTranslationSystem(): true
    {
        return true;
    }

    public function isAccountVerified(int $idCustomer): bool
    {
        return (int)Db::getInstance()->getValue(
                "SELECT verified FROM `" . _DB_PREFIX_ . "kpy_account_verify` WHERE id_customer = {$idCustomer}"
            ) === 1;
    }

    public function hookDisplayCustomerAccountTop(array $params): string
    {
        if (!$this->context->customer->isLogged() || $this->isAccountVerified($this->context->customer->id)) {
            return '';
        }

        /**
         * Se podría generar aquí el token y pasarlo al controlador, pero prefiero hacerlo después
         * y no mostrar nada innecesario en la URL. Además, así evito que algún bot intente reenviar el correo
         * tropecientas veces solo intentando acceder a la URL
         */

        return $this->fetch('module:' . $this->name . '/views/templates/hook/displayCustomerAccountTop.tpl', [
            'resend_link' => $this->context->link->getModuleLink($this->name, 'resend', [
                't' => $this->generateTokenForSignResendUrl(),
            ]),
        ]);
    }

    /**
     * Genera un token para comprobar si la petición de reenviar el email está hecha por un humano o no.
     * Este token NO ES EL QUE SE MANDA EN EL EMAIL
     */
    public function generateTokenForSignResendUrl(): string
    {
        $token = sha1($this->context->customer->email . '_' . $this->context->customer->id . '_' . microtime());
        $this->saveToken($this->context->customer, $token, false);

        return $token;
    }

    public function getTokenForSignResendUrl(int $idCustomer): string
    {
        return Db::getInstance()->getValue(
            "SELECT resend_token FROM `" . _DB_PREFIX_ . "kpy_account_verify` WHERE id_customer = $idCustomer"
        ) ?: '';
    }

    public function verifyAccountByCustomer(int $idCustomer): void
    {
        Db::getInstance()->update('kpy_account_verify', [
            'verified' => 1,
            'date_confirmation' => date(DATE_ATOM),
        ], 'id_customer =' . $idCustomer);
    }

    public function hookActionFrontControllerSetMedia(): void
    {
        if ($this->context->controller->getPageName() === 'my-account') {
            $this->context->controller->registerStylesheet(
                $this->name . '-style',
                'modules/' . $this->name . '/views/css/' . $this->name . '.css',
                [
                    'media' => 'all',
                    'priority' => 1000,
                ]
            );
        }
    }

    public function hookActionFrontControllerSetVariables(): array
    {
        return [
            'account_verified' => $this->context->customer->isLogged() && $this->isAccountVerified($this->context->customer->id),
        ];
    }

    public function renderWidget($hookName, array $configuration): string
    {
        return $this->fetch('module:' . $this->name . '/views/templates/widget/accountVerifyMessage.tpl', [
            'message' => $configuration['message'] ?? '',
            'resend_link' => $this->context->link->getModuleLink($this->name, 'resend', [
                't' => $this->generateTokenForSignResendUrl(),
            ]),
        ]);
    }

    public function getWidgetVariables($hookName, array $configuration)
    {
    }
}