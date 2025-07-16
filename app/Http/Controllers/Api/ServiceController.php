<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ServiceController extends Controller
{       
    public function index(Request $request)
    {
        $lang = $request->query('lang', 'en');

        $table = $lang === 'ar' ? 'services_ar' : 'services_en';

        $services = DB::table($table)->select('slug', 'name', 'description', 'price','image_url')->get();

        return response()->json($services);
    }

public function searchServiceByText(Request $request)
{
    $lang = $request->query('lang', 'en');
    $input = $request->query('text', '');

    $table = $lang === 'ar' ? 'services_ar' : 'services_en';
    $services = DB::table($table)->get(['name', 'description', 'slug']);

    // Normalization function
    $normalize = function ($string) use ($lang) {
        $string = trim($string);
        $string = mb_strtolower($string); // lowercase for Arabic too

        if ($lang !== 'ar') {
            $string = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $string); // remove accents
            $string = preg_replace('/[^a-z0-9\s]/', '', $string);         // strip punctuation
        }

        return $string;
    };

    // Singularize only for English
    $singularize = function ($word) use ($lang) {
        if ($lang === 'ar') return $word;
        if (str_ends_with($word, 'es')) {
            return substr($word, 0, -2);
        }
        if (str_ends_with($word, 's')) {
            return substr($word, 0, -1);
        }
        return $word;
    };

    // Normalize input
    $input = $normalize($input);
    $inputWords = array_filter(explode(' ', $input));
    $inputWords = array_map($singularize, $inputWords);

    // Debug logging
    \Log::info('Searching for: ' . json_encode($inputWords));

    // Match against service name or description
    foreach ($services as $service) {
        $name = $normalize($service->name);
        $desc = $normalize($service->description);

        foreach ($inputWords as $word) {
            if (
                mb_strpos($name, $word) !== false ||
                mb_strpos($desc, $word) !== false
            ) {
                \Log::info("Matched word: $word with service: {$service->name}");
                return response()->json(['slug' => $service->slug]);
            }
        }
    }

    return response()->json(['error' => 'No service matched'], 404);
}

    public function list(Request $request)
    {
        $lang = $request->query('lang', 'en');

        $table = $lang === 'ar' ? 'services_ar' : 'services_en';

        $services = DB::table($table)->select('slug', 'name', 'price', 'image_url')->get();

        return response()->json($services);
    }

    public function getSlugByName(Request $request)
{
    $lang = $request->query('lang', 'en');
    $name = $request->query('name');

    $service = DB::table('services_' . $lang)
        ->where('name', $name)
        ->first();

    if (!$service) {
        return response()->json(['error' => 'Service not found'], 404);
    }

    return response()->json(['slug' => $service->slug]);
}

    public function show($slug, Request $request)
{
    $lang = $request->query('lang', 'en');
    $table = $lang === 'ar' ? 'services_ar' : 'services_en';

    $service = DB::table($table)->where('slug', $slug)->first();

    if (!$service) {
        return response()->json(['error' => 'Service not found'], 404);
    }

    return response()->json($service);
}


public function store(Request $request)
{
    $lang = $request->query('lang', 'en');
    $table = $lang === 'ar' ? 'services_ar' : 'services_en';

    $request->validate([
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

        // Create directory if it doesn't exist
        if (!file_exists($destinationPath)) {
            mkdir($destinationPath, 0755, true);
        }

        $file->move($destinationPath, $filename);

        // Path saved to DB should be accessible publicly via the storage link
        $imagePath = '/storage/services/' . $filename;
    }

    DB::table($table)->insert([
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
        'name' => 'sometimes|string',
        'description' => 'sometimes|string',
        'price' => 'sometimes|numeric',
        'image' => 'nullable|image|max:2048',
    ]);

    $data = [];

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

    // return response()->json(['message' => 'Service updated']);
    return response()->json($request->all());
}



public function destroy($slug, Request $request)
{
    $lang = $request->query('lang', 'en');
    $table = $lang === 'ar' ? 'services_ar' : 'services_en';

    $service = DB::table($table)->where('slug', $slug)->first();

    if (!$service) {
        return response()->json(['error' => 'Service not found'], 404);
    }

    // Delete the image file from storage if it exists
    if (!empty($service->image_url)) {
        $imagePath = str_replace('/storage/', 'public/', $service->image_url);
        Storage::delete($imagePath);
    }

    DB::table($table)->where('slug', $slug)->delete();

    return response()->json(['message' => 'Service deleted successfully']);
}
}
