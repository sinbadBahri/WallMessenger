<?php

namespace App\Http\Controllers;

use App\Services\DelayedMessageService\DelayedMessageService;
use App\Services\MessageService\MessageService;
use App\Http\Requests\HandleMessageRequest;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class MainController extends Controller
{
    public function index(): InertiaResponse
    {
        return Inertia::render('Messenger/Home');
    }

    public function formPage(): InertiaResponse
    {
        return Inertia::render('Messenger/Form');
    }
}
