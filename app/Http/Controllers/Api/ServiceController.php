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
    $text = $request->query('text');

    if (!$text) {
        return response()->json(['error' => 'No input'], 400);
    }

    $table = $lang === 'ar' ? 'services_ar' : 'services_en';
    $normalize = function ($string) use ($lang) {
        $string = trim($string);
        $string = mb_strtolower($string);
        if ($lang === 'ar') {
            $string = preg_replace('/[\x{0610}-\x{061A}\x{064B}-\x{065F}\x{0670}]/u', '', $string);
        } else {
            $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string);
            // Replace hyphens with spaces BEFORE removing other punctuation
            $string = str_replace('-', ' ', $string);
            $string = preg_replace('/[^a-z0-9\s]/', '', $string);
            // Normalize multiple spaces to single space
            $string = preg_replace('/\s+/', ' ', $string);
        }
        return $string;
    };

    $input = $normalize($text);
    $inputWords = explode(' ', $input);
    $services = DB::table($table)->get();
    $bestMatch = null;
    $highestScore = 0;

    foreach ($services as $service) {
        $name = $normalize($service->name);
        $description = $normalize($service->description);
        $combinedText = $name . ' ' . $description;
        
        $score = 0;

        // 1. Exact phrase matching (highest priority)
        if (strpos($combinedText, $input) !== false) {
            $score += 100;
        }

        // 2. Individual word matching in name (high priority)
        $nameWords = explode(' ', $name);
        foreach ($inputWords as $inputWord) {
            if (strlen($inputWord) > 2) { // Skip very short words
                foreach ($nameWords as $nameWord) {
                    if (strpos($nameWord, $inputWord) !== false || strpos($inputWord, $nameWord) !== false) {
                        $score += 50; // High score for name word matches
                    }
                }
            }
        }

        // 3. Individual word matching in description (medium priority)
        $descriptionWords = explode(' ', $description);
        foreach ($inputWords as $inputWord) {
            if (strlen($inputWord) > 2) { // Skip very short words like "i", "to", "a"
                foreach ($descriptionWords as $descWord) {
                    if (strpos($descWord, $inputWord) !== false || strpos($inputWord, $descWord) !== false) {
                        $score += 25; // Medium score for description word matches
                    }
                }
            }
        }

        // 4. Similarity scoring as fallback (lower priority)
        similar_text($input, $name, $nameScore);
        similar_text($input, $description, $descScore);
        $score += ($nameScore * 1.5) + $descScore;

        if ($score > $highestScore) {
            $highestScore = $score;
            $bestMatch = $service;
        }
    }

    if ($bestMatch && $highestScore > 15) { // Lowered threshold for better matching
        return response()->json($bestMatch);
    } else {
        return response()->json(['message' => 'No close match found.'], 404);
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