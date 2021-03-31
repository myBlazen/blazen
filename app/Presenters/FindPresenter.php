<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;

use Nette\Database\Context;
use App\Model\UserManager;
use \Nette\Application\AbortException;

final class FindPresenter extends BasePresenter
{
    /**
     * @var Context
     */
    private $database;


    /**
     * @var UserManager
     */
    private $userManager;

    /**
     * FindPresenter constructor.
     * @param Context $database
     */
    public function __construct(Context $database, UserManager  $userManager)
    {
        parent::__construct($database, $userManager);
        $this->database = $database;
        $this->userManager = $userManager;
    }

    /**
     * @throws AbortException
     */
    protected function startup()
    {
        parent::startup();

        if (!$this->user->isLoggedIn()) {
            if ($this->user->logoutReason === Nette\Http\UserStorage::INACTIVITY) {
                $this->flashMessage('You have been signed out due to inactivity. Please sign in again.', 'alert-info');
            }
            $this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);
        }
    }

    public function RenderDefault(): void
    {
        $this->template->randomUsers = $this->userManager->getRandomUsers(10);
    }




}