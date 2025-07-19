<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{
    // List all services grouped by category (or just all)
    public function index(Request $request)
    {
        $lang = $request->query('lang', 'en');
        $table = $lang === 'ar' ? 'services_ar' : 'services_en';
        $categoryNameField = $lang === 'ar' ? 'name_ar' : 'name_en';

        $services = DB::table($table)
            ->join('service_categories', "$table.category_id", '=', 'service_categories.id')
            ->select(
                "$table.slug",
                "$table.name",
                "$table.description",
                "$table.price",
                "$table.image_url",
                "service_categories.slug as category_slug",
                "service_categories.$categoryNameField as category_name"
            )
            ->get()
            ->groupBy('category_slug');

        // Format grouped services to arrays of values
        $result = $services->map(fn($group) => $group->values());

        return response()->json($result);
    }

    function stripArabicDiacritics($text) {
    return preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}]/u', '', $text);
}

public function searchServiceByText(Request $request)
{
    $lang = $request->query('lang', 'en');
    $text = $request->input('text', '');
    $table = $lang === 'ar' ? 'services_ar' : 'services_en';

    $services = DB::table($table)->get();

    $normalize = function ($string) use ($lang) {
        $string = strtolower(trim($string));
        if ($lang === 'ar') {
            // Remove Arabic diacritics
            $string = preg_replace('/[\p{Mn}]/u', '', $string);
        }
        return preg_replace('/[^\p{L}\p{N}]+/u', '', $string);
    };

    $input = $normalize($text);

    $suggestions = [];
    $bestMatch = null;
    $bestScore = 0;

    foreach ($services as $service) {
        $name = $normalize($service->name);
        $desc = $normalize($service->description ?? '');

        similar_text($input, $name, $nameScore);
        similar_text($input, $desc, $descScore);

        $score = max($nameScore, $descScore);

        if ($score > 40) {
            $suggestions[] = [
                'id' => $service->id,
                'name' => $service->name,
                'description' => $service->description,
                'image' => $service->image,
                'score' => $score,
            ];
        }

        if ($score > $bestScore) {
            $bestScore = $score;
            $bestMatch = $service;
        }
    }

    // Sort suggestions by score
    usort($suggestions, fn($a, $b) => $b['score'] <=> $a['score']);

    if ($bestScore > 60) {
        return response()->json([
            'match' => $bestMatch,
            'suggestions' => $suggestions,
        ]);
    } elseif (count($suggestions)) {
        return response()->json([
            'match' => null,
            'suggestions' => $suggestions,
        ]);
    } else {
        return response()->json([
            'match' => null,
            'suggestions' => [],
            'message' => 'No close match found.',
        ], 404);
    }
}
    // List services without category grouping (simple list)
    public function list(Request $request)
    {
        $lang = $request->query('lang', 'en');
        $table = $lang === 'ar' ? 'services_ar' : 'services_en';

        $services = DB::table($table)
            ->select('slug', 'name', 'price', 'image_url')
            ->get();

        return response()->json($services);
    }

    // Get slug by exact service name (case sensitive)
    public function getSlugByName(Request $request)
    {
        $lang = $request->query('lang', 'en');
        $name = $request->query('name');
        $table = 'services_' . $lang;

        $service = DB::table($table)
            ->where('name', $name)
            ->first();

        if (!$service) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        return response()->json(['slug' => $service->slug]);
    }

    // Show single service details by slug
    public function show($slug, Request $request)
    {
        $lang = $request->query('lang', 'en');
        $table = $lang === 'ar' ? 'services_ar' : 'services_en';

        $service = DB::table($table)
            ->where('slug', $slug)
            ->first();

        if (!$service) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        return response()->json($service);
    }

    // Store new service (with category_id)
    public function store(Request $request)
    {
        $lang = $request->query('lang', 'en');
        $table = $lang === 'ar' ? 'services_ar' : 'services_en';

        $request->validate([
            'category_id' => 'required|exists:service_categories,id',
            'name' => 'required|string',
            'slug' => 'required|string|unique:' . $table,
            'description' => 'required|string',
            'price' => 'required|numeric',
            'image' => 'nullable|image|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();

            $destinationPath = storage_path('app/public/services');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $filename);
            $imagePath = '/storage/services/' . $filename;
        }

        DB::table($table)->insert([
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => $request->slug,
            'description' => $request->description,
            'price' => $request->price,
            'image_url' => $imagePath,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['message' => 'Service created']);
    }

    public function update(Request $request, $slug)
    {
        $lang = $request->query('lang', 'en');
        $table = $lang === 'ar' ? 'services_ar' : 'services_en';

        $service = DB::table($table)->where('slug', $slug)->first();
        if (!$service) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        $request->validate([
            'category_id' => 'sometimes|exists:service_categories,id',
            'name' => 'sometimes|string',
            'description' => 'sometimes|string',
            'price' => 'sometimes|numeric',
            'image' => 'nullable|image|max:2048',
        ]);

        $data = [];

        if ($request->has('category_id')) {
            $data['category_id'] = $request->category_id;
        }
        if ($request->has('name')) {
            $data['name'] = $request->input('name');
        }
        if ($request->has('description')) {
            $data['description'] = $request->input('description');
        }
        if ($request->has('price')) {
            $data['price'] = $request->input('price');
        }

        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = uniqid() . '.' . $file->getClientOriginalExtension();

            $destinationPath = storage_path('app/public/services');
            if (!file_exists($destinationPath)) {
                mkdir($destinationPath, 0755, true);
            }

            $file->move($destinationPath, $filename);
            $data['image_url'] = '/storage/services/' . $filename;
        }

        if (empty($data)) {
            return response()->json(['message' => 'No fields to update'], 400);
        }

        $data['updated_at'] = now();

        try {
            DB::table($table)->where('slug', $slug)->update($data);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Update failed', 'details' => $e->getMessage()], 500);
        }

        return response()->json(['message' => 'Service updated']);
    }
    public function getCategories(Request $request)
{
    $lang = $request->query('lang', 'en');
    $categoryNameField = $lang === 'ar' ? 'name_ar' : 'name_en';

    $categories = DB::table('service_categories')
        ->select('id', 'slug', "$categoryNameField as name")
        ->get();

    return response()->json($categories);
}

    // Delete service by slug (and delete image file)
    public function destroy($slug, Request $request)
    {
        $lang = $request->query('lang', 'en');
        $table = $lang === 'ar' ? 'services_ar' : 'services_en';

        $service = DB::table($table)->where('slug', $slug)->first();
        if (!$service) {
            return response()->json(['error' => 'Service not found'], 404);
        }

        // Delete image file if exists
        if (!empty($service->image_url)) {
            $imagePath = str_replace('/storage/', 'public/', $service->image_url);
            Storage::delete($imagePath);
        }

        DB::table($table)->where('slug', $slug)->delete();

        return response()->json(['message' => 'Service deleted successfully']);
    }
}