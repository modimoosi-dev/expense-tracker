<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Kreait\Laravel\Firebase\Facades\Firebase;

class FirebaseTokenController extends Controller
{
    public function token(Request $request): JsonResponse
    {
        $userId = $request->get('user_id', 1);

        $auth  = Firebase::auth();
        $token = $auth->createCustomToken((string) $userId);

        return response()->json(['token' => $token->toString()]);
    }
}
