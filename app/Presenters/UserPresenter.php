<?php

namespace App\Presenters;

use Nette;
use App\Model\PostManager;
use Nette\ComponentModel\IComponent;
use Nette\Application\UI\Form;
use Nette\Security\Passwords;
use Nette\Utils\Image;

class UserPresenter extends BasePresenter
{
    /**
     * @var Nette\Database\Context
     */
    private $database;

    /**
     * @var PostManager
     */
    private $postManager;

    /**
     * @var Passwords
     */
    private $passwords;

    /**
     * UserPresenter constructor.
     * @param Nette\Database\Context $database
     * @param PostManager $postManager
     * @param Passwords $passwords
     */
    public function __construct(Nette\Database\Context $database, PostManager $postManager, Passwords $passwords)
    {
        parent::__construct($database);
        $this->database = $database;
        $this->postManager = $postManager;
        $this->passwords = $passwords;
    }


    /**
     * @throws \Nette\Application\AbortException
     */
    protected function startup()
    {
        parent::startup();

        if (!$this->user->isLoggedIn()) {
            if ($this->user->logoutReason === Nette\Http\UserStorage::INACTIVITY) {
                $this->flashMessage('You have been signed out due to inactivity. Please sign in again.');
            }
            $this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
        }
    }


    public function RenderProfile(): void
    {
        $this->template->wall_posts = $this->postManager->getPostsByUser($this->getUser()->getId(),true);
    }


    /**
     * @return Form
     */
    protected function createComponentUploadImageForm(): Form
    {
        $form = new Form();

        $form->addHidden('user_id', $this->getUser()->getId());

        $form->addUpload('image','images')
            ->setRequired()
            ->addRule($form::IMAGE, 'Please select file format JPEG, PNG or GIF.')
            ->addRule($form::MAX_FILE_SIZE, 'Maximum size is 1 MB.', 1024 * 1024);

        $form->addSubmit('uploadImage', 'Upload Image');

        $form->onSuccess[] = [$this, 'uploadImageSucceeded'];

        return $form;
    }

    /**
     * @param Form $form
     * @param \stdClass $values
     */
    public function uploadImageSucceeded(Form $form, \stdClass $values): void
    {
        $path = "/users_images/" . $values->user_id . "/profile_image/" . $values->image->getName();

        $data = array(
            'user_profile_img_path' => $path
        );

        $user = $this->database->table('users')->get($this->getUser()->getId());
        $user->update($data);

        $values->image->move("../www" . $path);

        $this->flashMessage('Image was uploaded');

        $this->redirect('User:settings');
    }

    /**
     * @return Form
     */
    protected function createComponentCommentPostForm(): Form
    {
        $form = new Form;

        $form->addHidden('user_id', $this->getUser()->getId())
            ->setRequired();

        $form->addHidden('wall_post_id')
            ->setRequired();

        $form->addTextArea('comment_content', 'Comment')
            ->setRequired();

        $form->addSubmit('commentPost', 'Comment');

        $form->onSuccess[] = [$this, 'commentPostFormSucceeded'];

        return $form;
    }

    /**
     * @param Form $form
     * @param array $values
     */
    public function commentPostFormSucceeded(Form $form, array $values): void
    {
        $this->database->table('comments')->insert($values);

        $values = null;

        $this->flashMessage('Comment was published');

        $this->redirect('User:profile');

    }

    public function actionSettings()
    {
        $data = $this->getLoggedUserData();
        $this['generalSettingsForm']->setDefaults($data);
        $this['informationSettingsForm']->setDefaults($data);
        $this['connectionsSettingsForm']->setDefaults($data);

    }

    /**
     * @return Form
     */
    public function createComponentGeneralSettingsForm():Form
    {
        $form = new Form;

        $form->addText('firstname', 'First Name')
            ->setRequired();

        $form->addText('lastname', 'Last Name')
            ->setRequired();

        $form->addText('username', 'Username')
            ->setRequired();

        $form->addPassword('password','Password')
            ->setRequired();

        $form->addSubmit('saveChanges', 'Save Changes');

        $form->onSuccess[] = [$this, 'GeneralSettingsFormSucceeded'];

        return $form;
    }

    /**
     * @param Form $form
     * @param array $values
     * @throws Nette\Application\AbortException
     */
    public function GeneralSettingsFormSucceeded(Form $form, array $values):void
    {

        $user = $this->database->table('users')->get($this->getUser()->getId());

        if($user){
            if($this->passwords->verify($values['password'], $user['password'])){
                unset($values['password']);
                if($values['birthday'] ===''){
                    unset($values['birthday']);
                }
                $user->update($values);

                $this->flashMessage('Changes saved', 'alert-success');

                $this->redirect('User:settings');
            }
            else{
                $this->flashMessage('Your password is incorect','alert-danger');

                $this->redirect('User:settings');
            }
        }
        else{
            $this->flashMessage('Uups something went wrong');

            $this->redirect('User:settings');
        }

    }

    /**
     * @return Form
     */
    public function createComponentInformationSettingsForm():Form
    {
        $form = new Form;

        $form->addTextArea('about', 'About Me');

        $form->addText('birthday', 'Birthday')
            ->setHtmlType('date');

        $form->addText('birthplace', 'Birthplace');

        $form->addText('lives_in', 'Lives In');

        $form->addText('occupation', 'Occupation');

        $form->addSelect('sex', 'Sex')
            ->setItems(array(
                '' => 'select...',
                'Male' => 'Male',
                'Female' => 'Female'
            ));

        $form->addSelect('status', 'Status')
            ->setItems(array(
                '' => 'select...',
                'Single' => 'Single',
                'In Relationship' => 'In Relationship'
                ));

        $form->addPassword('password','Password')
            ->setRequired();


        $form->addSubmit('saveChanges', 'Save Changes');

        $form->onSuccess[] = [$this, 'InformationSettingsFormSucceeded'];

        return $form;
    }

    /**
     * @param Form $form
     * @param array $values
     * @throws Nette\Application\AbortException
     */
    public function InformationSettingsFormSucceeded(Form $form, array $values): void
    {
        $user = $this->database->table('users')->get($this->getUser()->getId());

        if($user){
            if($this->passwords->verify($values['password'], $user['password'])){
                unset($values['password']);
                $user->update($values);

                $this->flashMessage('Changes saved','alert-success');

                $this->redirect('User:settings');
            }
            else{
                $this->flashMessage('Your password is incorect','alert-danger');

                $this->redirect('User:settings');
            }
        }
        else{
            $this->flashMessage('Uups something went wrong','alert-danger');

            $this->redirect('User:settings');
        }
    }

    public function createComponentConnectionsSettingsForm():Form
    {
        $form = new Form;

        $form->addText('facebook_name', 'Facebook');

        $form->addText('instagram_name', 'Instagram');

        $form->addPassword('password','Password')
            ->setRequired();

        $form->addSubmit('saveChanges', 'Save Changes');

        $form->onSuccess[] = [$this, 'ConnectionsSettingsFormSucceeded'];

        return $form;
    }


    /**
     * @param Form $form
     * @param array $values
     * @throws Nette\Application\AbortException
     */
    public function ConnectionsSettingsFormSucceeded(Form $form, array $values): void
    {
        $user = $this->database->table('users')->get($this->getUser()->getId());

        if($user){
            if($this->passwords->verify($values['password'], $user['password'])){
                unset($values['password']);
                $user->update($values);

                $this->flashMessage('Changes saved','alert-success');

                $this->redirect('User:settings');
            }
            else{
                $this->flashMessage('Your password is incorect','alert-danger');

                $this->redirect('User:settings');
            }
        }
        else{
            $this->flashMessage('Uups something went wrong','alert-danger');

            $this->redirect('User:settings');
        }
    }


}