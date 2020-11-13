<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Model\RegisterUserManager;
use Latte\Engine;
use Nette\Application\UI\Form;
use Nette\Database\Context;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Mail\Message;
use Nette\Mail\SendException;
use Nette\Mail\SendmailMailer;
use Nette\Security\AuthenticationException;
use Nette\Security\Passwords;

class RegisterPresenter extends BasePresenter
{
    protected function beforeRender()
    {
        if ($this->getUser()->isLoggedIn()) {
            $this->redirect('Homepage:');
        }
    }

    private $database;
    private $registerUserManager;
    private $passwords;

    public function __construct
    (
        Context $database,
        RegisterUserManager $registerUserManager,
        Passwords $passwords
    )
    {
        $this->database = $database;
        $this->registerUserManager = $registerUserManager;
        $this->passwords = $passwords;
    }

    protected function createComponentRegisterForm(): Form
    {
        $form = new Form;

        $form->addText('firstname')
            ->setRequired('Firstrname is required!');

        $form->addText('lastname')
            ->setRequired('Lastname is required!');

        $form->addText('username')
            ->setRequired('Username is required!');

        $form->addEmail('email')
            ->setRequired('Email is required!');

        $passwordInput = $form->addPassword('password', 'Password')
            ->setRequired('Please enter password');

        $form->addPassword('repeat_password', 'Password (verify)')
            ->setRequired('Please enter password for verification')
            ->addRule($form::EQUAL, 'Password verification failed. Passwords do not match', $passwordInput);

        $form->addSubmit('register', 'Register Account');

        $form->onSuccess[] = [$this, 'RegisterFormSucceeded'];
        return $form;
    }

    public function RegisterFormSucceeded(Form $form, \stdClass $values): void
    {
        $latte = new Engine();
        $mail = new Message();

        $params = [
          'username' => $values->username
        ];

        $mail ->setFrom('my.blazen@gmail.com', 'BLAZEN')
            ->addTo($values->email)
            ->setSubject('NoReplay - BLAZEN registration')
            ->setHtmlBody(
                $latte->renderToString(__DIR__.'/templates/Email/registerEmail.latte', $params)
            );

        $mailer = new SendmailMailer();
        $mailer->send($mail);



        try{
            $this->database->table('users')->insert([
                'password'  => $this->passwords->hash($values->password),
                'email'     => $values->email,
                'lastname'  => $values->lastname,
                'firstname' => $values->firstname,
                'username'  => $values->username,
            ]);
            $this->redirect('Homepage:');
        }catch (UniqueConstraintViolationException $e){
            $this->flashMessage('User already exists');
        }
    }

}
