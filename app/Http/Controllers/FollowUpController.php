<?php

namespace App\Http\Controllers;

use App\Services\FollowUpService\FollowUpService;
use Illuminate\Http\JsonResponse;

class FollowUpController extends Controller
{
    private FollowUpService $followUpService;

    public function __construct(FollowUpService $followUpService)
    {
        $this->followUpService = $followUpService;
    }

    public function index(): JsonResponse
    {
        $result = $this->followUpService->processFollowUps();

        return response()->json($result, $result['success'] ? 200 : 400);
    }
}
