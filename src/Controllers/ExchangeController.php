<?php

namespace Larry\Larry\Controllers;

use App\Http\Controllers\Controller;
use Larry\Larry\Models\Exchange;

class ExchangeController extends Controller
{
    final public function show($id)
    {
        $this->middleware('auth');

        $exchange = Exchange::findOrFail($id);

        if (!$exchange->finished) {
            return response()->json([
                'type' => 'processing',
            ]);
        }

        $promptResponse = $exchange->promptResponses()->latest()->first();

        if ($promptResponse->function_name === false) {
            return response()->json([
                'type' => 'actionable',
                'speak' => $promptResponse->content
            ]);
        }

        // TODO: If front end navigation???

        // If front end function
        return response()->json([
            'type' => 'actionable',
            'execute' => $promptResponse->function_name,
            'with' => $promptResponse->function_arguments,
            // TODO:
            'reprompt' => false,
        ]);
    }
}
