<?php

namespace App\Http\Controllers;

use App\Models\SalesPage;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SalesPageController extends Controller
{
    public function generate(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'description' => 'required|string',
            'features' => 'required|array',
            'target_audience' => 'required|string',
            'template' => 'required|string',
            'price' => 'required|string',
            'usp' => 'required|string',
        ]);

        $prompt = "
        You are a senior direct-response copywriter.

        Generate a structured sales page in VALID JSON ONLY.

        STRICT RULES:
        - Return ONLY valid JSON
        - No markdown
        - No backticks
        - No explanation
        - Follow schema EXACTLY

        Product Name: {$validated['name']}
        Description: {$validated['description']}
        Features: ".implode(', ', $validated['features'])."
        Target Audience: {$validated['target_audience']}
        Price: {$validated['price']}
        Unique Selling Point: {$validated['usp']}

        OUTPUT SCHEMA (MUST FOLLOW EXACTLY):

        {
        \"headline\": \"string\",
        \"subheadline\": \"string\",
        \"description\": \"string\",
        \"benefits\": [\"string\"],
        \"features\": [
            {
            \"name\": \"string\",
            \"description\": \"string\"
            }
        ],
        \"social_proof\": \"string\",
        \"price\": {
            \"amount\": number,
            \"currency\": \"IDR\",
            \"unit\": \"string\"
        },
        \"unique_selling_point\": \"string\",
        \"cta\": {
            \"button_text\": \"string\",
            \"link\": \"string\"
        }
        }
        ";

        // 🔥 OPENROUTER (GRATIS)
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.env('OPENROUTER_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => 'openrouter/auto',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $data = $response->json();

        $content = $data['choices'][0]['message']['content'] ?? null;

        if (! $content) {
            return response()->json([
                'error' => 'No AI response',
                'debug' => $data,
            ], 500);
        }

        // bersihin ```json
        $content = preg_replace('/```json|```/', '', $content);

        $generated = json_decode($content, true);

        if (! $generated) {
            return response()->json([
                'error' => 'Invalid JSON from AI',
                'raw' => $content,
            ], 500);
        }

        // 💾 Save
        $page = SalesPage::create([
            'user_id' => auth()->id(),
            'product_name' => $validated['name'],
            'input_data' => $validated,
            'generated_content' => $generated,
            'template' => $validated['template'],
        ]);

        return response()->json($page);
    }

    public function index()
    {
        return SalesPage::where('user_id', auth()->id())
            ->latest()
            ->paginate(6);
    }

    public function show($id)
    {
        try {
            $data = SalesPage::where('user_id', auth()->id())
                ->findOrFail($id);

            return response()->json([
                'message' => 'Success get data',
                'data' => $data,
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'message' => 'Sales page not found',
            ], 404);
        }
    }

    public function update(Request $request, $id)
    {
        $page = SalesPage::where('user_id', auth()->id())
            ->findOrFail($id);
        $validated = $request->validate([
            'name' => 'sometimes|required|string',
            'description' => 'sometimes|required|string',
            'features' => 'sometimes|required|array',
            'target_audience' => 'sometimes|required|string',
            'template' => 'sometimes|required|string',
            'price' => 'sometimes|required|string',
            'usp' => 'sometimes|required|string',
        ]);
        $newInputData = array_merge($page->input_data, $validated);
        $prompt = "
        You are a senior direct-response copywriter.

        Your task is to REGENERATE a better version of an existing sales page.

        STRICT RULES:
        - Return ONLY valid JSON
        - No markdown
        - No backticks
        - No explanation
        - Must follow EXACT same schema as provided

        Product Name: {$newInputData['name']}
        Description: {$newInputData['description']}
        Features: ".implode(', ', $newInputData['features'])."
        Target Audience: {$newInputData['target_audience']}
        Price: {$newInputData['price']}
        Unique Selling Point: {$newInputData['usp']}

        OUTPUT MUST MATCH THIS EXACT SCHEMA:

        {
        \"headline\": \"string\",
        \"subheadline\": \"string\",
        \"description\": \"string\",
        \"benefits\": [\"string\"],
        \"features\": [
            {
            \"name\": \"string\",
            \"description\": \"string\"
            }
        ],
        \"social_proof\": \"string\",
        \"price\": {
            \"amount\": number,
            \"currency\": \"IDR\",
            \"unit\": \"string\"
        },
        \"unique_selling_point\": \"string\",
        \"cta\": {
            \"button_text\": \"string\",
            \"link\": \"string\"
        }
        }
        ";

        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.env('OPENROUTER_API_KEY'),
            'Content-Type' => 'application/json',
        ])->post('https://openrouter.ai/api/v1/chat/completions', [
            'model' => 'openrouter/auto',
            'messages' => [
                ['role' => 'user', 'content' => $prompt],
            ],
        ]);

        $data = $response->json();

        $content = $data['choices'][0]['message']['content'] ?? null;

        if (! $content) {
            return response()->json([
                'error' => 'No AI response',
                'debug' => $data,
            ], 500);
        }

        $content = preg_replace('/```json|```/', '', $content);

        $generated = json_decode($content, true);

        if (! $generated) {
            return response()->json([
                'error' => 'Invalid JSON from AI',
                'raw' => $content,
            ], 500);
        }

        $page->update([
            'product_name' => $newInputData['name'],
            'input_data' => $newInputData,      // Simpan input yang sudah diperbarui
            'generated_content' => $generated,         // Simpan hasil AI yang baru
            'template' => $newInputData['template'] ?? $page->template,
        ]);

        return response()->json($page);
    }

    public function destroy($id)
    {
        $page = SalesPage::where('user_id', auth()->id())
            ->findOrFail($id);

        $page->delete();

        return response()->json([
            'message' => 'Deleted successfully',
        ]);
    }
}
