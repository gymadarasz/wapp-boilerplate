<?php declare(strict_types = 1);

/**
 * PHP version 7.4
 *
 * @category  PHP
 * @package   Madsoft\Library\Test\Account
 * @author    Gyula Madarasz <gyula.madarasz@gmail.com>
 * @copyright 2020 Gyula Madarasz
 * @license   Copyright (c) All rights reserved.
 * @link      this
 */

namespace Madsoft\Library\Test\Account;

use Madsoft\Library\Account\Reset;
use Madsoft\Library\Account\AccountValidator;
use Madsoft\Library\Config;
use Madsoft\Library\Crud;
use Madsoft\Library\Encrypter;
use Madsoft\Library\Invoker;
use Madsoft\Library\Mailer;
use Madsoft\Library\Merger;
use Madsoft\Library\Messages;
use Madsoft\Library\Params;
use Madsoft\Library\Responder\TemplateResponder;
use Madsoft\Library\Row;
use Madsoft\Library\Template;
use Madsoft\Library\Test;
use Madsoft\Library\Token;

/**
 * ResetTest
 *
 * @category  PHP
 * @package   Madsoft\Library\Test\Account
 * @author    Gyula Madarasz <gyula.madarasz@gmail.com>
 * @copyright 2020 Gyula Madarasz
 * @license   Copyright (c) All rights reserved.
 * @link      this
 *
 * @suppress PhanUnreferencedClass
 *
 * @SuppressWarnings(PHPMD.Superglobals)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ResetTest extends Test
{
    /**
     * Method testDoResetDbFails
     *
     * @param Invoker $invoker invoker
     *
     * @return void
     *
     * @suppress PhanUnreferencedPublicMethod
     * @suppress PhanTypeMismatchArgument
     * @suppress PhanUndeclaredClassMethod
     */
    public function testDoResetDbFails(Invoker $invoker): void
    {
        $template = $invoker->getInstance(Template::class);
        $merger = $invoker->getInstance(Merger::class);
        $crud = $this->getMock(Crud::class);
        $crud->shouldReceive('get')->andReturn(
            (new Row)->setFields(['email' => 'emailaddr1'])
        );
        $crud->shouldReceive('set')->andReturnFalse();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['email'] = 'emailaddr1';
        $params = $invoker->getInstance(Params::class);
        $validator = $this->getMock(AccountValidator::class);
        $validator->shouldReceive('validateReset')->andReturn([]);
        $mailer = $invoker->getInstance(Mailer::class);
        $config = $invoker->getInstance(Config::class);
        $messages = new Messages();
        $responder = new TemplateResponder($messages, $merger, $template);
        $reset = new Reset(
            $responder,
            $crud, // @phpstan-ignore-line
            $params,
            $validator, // @phpstan-ignore-line
            $mailer,
            $config
        );
        $encrypter = new Encrypter();
        $token = new Token($encrypter);
        $result = $reset->doReset($token);
        $this->assertStringContains('Token is not updated', $result);
    }
    
    /**
     * Method testDoResetMailFails
     *
     * @param Invoker $invoker invoker
     *
     * @return void
     *
     * @suppress PhanUnreferencedPublicMethod
     * @suppress PhanTypeMismatchArgument
     * @suppress PhanUndeclaredClassMethod
     */
    public function testDoResetMailFails(Invoker $invoker): void
    {
        $template = $invoker->getInstance(Template::class);
        $merger = $invoker->getInstance(Merger::class);
        $crud = $this->getMock(Crud::class);
        $crud->shouldReceive('get')->andReturn(
            (new Row)->setFields(['email' => 'emailaddr1'])
        );
        $crud->shouldReceive('set')->andReturnTrue();
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_GET['email'] = 'emailaddr1';
        $params = $invoker->getInstance(Params::class);
        $validator = $this->getMock(AccountValidator::class);
        $validator->shouldReceive('validateReset')->andReturn([]);
        $mailer = $this->getMock(Mailer::class);
        $mailer->shouldReceive('send')->andReturnFalse();
        $config = $invoker->getInstance(Config::class);
        $messages = new Messages();
        $responder = new TemplateResponder($messages, $merger, $template);
        $reset = new Reset(
            $responder,
            $crud, // @phpstan-ignore-line
            $params,
            $validator, // @phpstan-ignore-line
            $mailer, // @phpstan-ignore-line
            $config
        );
        $encrypter = new Encrypter();
        $token = new Token($encrypter);
        $result = $reset->doReset($token);
        $this->assertStringContains('Email sending failed', $result);
    }
}
