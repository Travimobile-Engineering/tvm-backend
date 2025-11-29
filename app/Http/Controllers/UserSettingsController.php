<?php

namespace App\Http\Controllers;

use App\Http\Requests\SetSecurityAnswerRequest;
use App\Services\UserSettingsService;
use App\Trait\HttpResponse;
use Illuminate\Http\Request;

class UserSettingsController extends Controller
{
    use HttpResponse;

    public function __construct(
        protected UserSettingsService $userSettingsService
    ) {}

    public function getQuestions()
    {
        return $this->userSettingsService->getQuestions();
    }

    public function setSecurityAnswer(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'answer' => 'required|string|min:3',
            'security_question_id' => 'required|exists:security_questions,id',
        ]);

        return $this->error(null, 'Contact support for more information.', 400);
        // return $this->userSettingsService->setSecurityAnswer($request);
    }

    public function createPassword(Request $request)
    {
        $request->validate([
            'email' => 'required|string',
            'password' => 'required|string|min:8|confirmed',
        ]);

        return $this->error(null, 'Contact support for more information.', 400);
        // return $this->userSettingsService->createPassword($request);
    }

    public function changeSecurityAnswer(SetSecurityAnswerRequest $request)
    {
        return $this->userSettingsService->changeSecurityAnswer($request);
    }

    public function getUserQuestion()
    {
        return $this->userSettingsService->getUserQuestion();
    }

    public function verifySecurityAnswer(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'answer' => 'required|string|min:3',
        ]);

        return $this->error(null, 'Contact support for more information.', 400);
        // return $this->userSettingsService->verifySecurityAnswer($request);
    }
}
