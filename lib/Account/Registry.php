<?php declare(strict_types = 1);

/**
 * PHP version 7.4
 *
 * @category  PHP
 * @package   Madsoft\Library\Account
 * @author    Gyula Madarasz <gyula.madarasz@gmail.com>
 * @copyright 2020 Gyula Madarasz
 * @license   Copyright (c) All rights reserved.
 * @link      this
 */

namespace Madsoft\Library\Account;

use Madsoft\Library\Config;
use Madsoft\Library\Crud;
use Madsoft\Library\Encrypter;
use Madsoft\Library\Mailer;
use Madsoft\Library\Params;
use Madsoft\Library\Responder\TemplateResponder;
use Madsoft\Library\Session;
use Madsoft\Library\Token;

/**
 * Registry
 *
 * @category  PHP
 * @package   Madsoft\Library\Account
 * @author    Gyula Madarasz <gyula.madarasz@gmail.com>
 * @copyright 2020 Gyula Madarasz
 * @license   Copyright (c) All rights reserved.
 * @link      this
 */
class Registry extends AccountConfig
{
    protected TemplateResponder $responder;
    protected Session $session;
    protected Crud $crud;
    protected Params $params;
    protected AccountValidator $validator;
    protected Mailer $mailer;
    protected Config $config;
    
    /**
     * Method __construct
     *
     * @param TemplateResponder $responder responder
     * @param Session           $session   session
     * @param Crud              $crud      crud
     * @param Params            $params    params
     * @param AccountValidator  $validator validator
     * @param Mailer            $mailer    mailer
     * @param Config            $config    config
     */
    public function __construct(
        TemplateResponder $responder,
        Session $session,
        Crud $crud,
        Params $params,
        AccountValidator $validator,
        Mailer $mailer,
        Config $config
    ) {
        $this->responder = $responder;
        $this->session = $session;
        $this->crud = $crud;
        $this->params = $params;
        $this->validator = $validator;
        $this->mailer = $mailer;
        $this->config = $config;
    }
    
    /**
     * Method registry
     *
     * @return string
     *
     * @suppress PhanUnreferencedPublicMethod
     */
    public function registry(): string
    {
        return $this->responder->setTplfile('registry.phtml')->getResponse();
    }
    
    
    /**
     * Method doRegistry
     *
     * @param Token     $tokengen  tokengen
     * @param Encrypter $encrypter encrypter
     *
     * @return string
     *
     * @suppress PhanUnreferencedPublicMethod
     */
    public function doRegistry(Token $tokengen, Encrypter $encrypter): string
    {
        $errors = $this->validator->validateRegistry($this->params);
        if ($errors) {
            return $this->responder->setTplfile('registry.phtml')->getErrorResponse(
                'Invalid registration data',
                $errors
            );
        }
        
        $email = $this->params->get('email');
        $token = $tokengen->generate();
        
        $user = $this->crud->get('user', ['email'], ['email' => $email], 1, 0, -1);
        if ($user->get('email') === $email) {
            return $this->responder->setTplfile('registry.phtml')->getErrorResponse(
                'Email address already registered',
                $errors
            );
        }
        
        if (!$this->crud->add(
            'user',
            [
                'email' => $email,
                'hash' => $encrypter->encrypt($this->params->get('password')),
                'token' => $token,
                'active' => '0',
            ],
            -1
        )
        ) {
            return $this->responder->setTplfile('registry.phtml')->getErrorResponse(
                'User is not saved'
            );
        }
        $this->session->set('resend', ['email' => $email, 'token' => $token]);
        
        if (!$this->sendActivationEmail($email, $token)) {
            return $this->responder->setTplfile('activate.phtml')->getErrorResponse(
                'Activation email is not sent',
                [],
                $user->getFields()
            );
        }
        
        return $this->responder->setTplfile('activate.phtml')->getSuccessResponse(
            'We sent an activation email to your email account, '
                . 'please follow the instructions.'
        );
    }
    
    /**
     * Method doResend
     *
     * @return string
     *
     * @suppress PhanUnreferencedPublicMethod
     */
    public function doResend(): string
    {
        $resend = $this->session->get('resend');
        $email = $resend['email'];
        $token = $resend['token'];
        if (!$this->sendActivationEmail($email, $token)) {
            return $this->responder->setTplfile('activate.phtml')->getErrorResponse(
                'Activation email is not sent'
            );
        }
        
        return $this->responder->setTplfile('activate.phtml')->getSuccessResponse(
            'We re-sent an activation email to your email account, '
                . 'please follow the instructions.'
        );
    }

    /**
     * Method sendActivationEmail
     *
     * @param string $email email
     * @param string $token token
     *
     * @return bool
     */
    protected function sendActivationEmail(string $email, string $token): bool
    {
        $message = $this->responder->setTplfile(
            'emails/activation.phtml'
        )->getResponse(
            [
                'base' => $this->config->get('Site')->get('base'),
                'token' => $token,
            ]
        );
        return $this->mailer->send(
            $email,
            'Activate your account',
            $message
        );
    }
}
