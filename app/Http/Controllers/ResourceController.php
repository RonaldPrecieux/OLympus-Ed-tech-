<?php

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Course;
use App\Models\Resource;
use Illuminate\Support\Facades\Validator;

class ResourceController extends Controller
{
    // Créer un nouveau cours
    public function createCourse(Request $request)
    {
        $validator =Validator::make($request->all(),[
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        try {
            //code...
            $data = $request->only('name', 'description');
            $course = Course::create($data);
           return response()->json($course, 201);


        } catch (\Throwable $e) {
            return response()->json(['error' => 'Une error Call to admin please! ' . $e->getMessage()], 401);

        }


    }

    // Récupérer la liste des cours
    public function getCourses()
    {
        $courses = Course::all();

        return response()->json($courses);
    }

    // Télécharger une ressource
    public function uploadResource(Request $request)
    {
        $validator =Validator::make($request->all(),[
            'course_id' => 'required|exists:courses,id',
            'file' => 'required|file|mimes:pdf,mp4|max:10240',
        ]);
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }
        try {
            //code...
            $course = Course::findOrFail($request->input('course_id'));
            $file = $request->file('file');
            $path = $file->store('resources');
    
            $resource = new Resource([
                'course_id' => $course->id,
                'file_path' => $path,
                'file_name' => $file->getClientOriginalName(),
            ]);
            $resource->save();
    
            return response()->json($resource, 201);
    
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Une error Call to admin please! ' . $e->getMessage()], 401);

        }

    }

    // Obtenir les ressources d'un cours spécifique
    public function getResources($course_id)
    {
        $course = Course::findOrFail($course_id);
        $resources = $course->resources;

        return response()->json($resources);
    }

    // Supprimer une ressource
    public function deleteResource($resource_id)
    {
        $resource = Resource::findOrFail($resource_id);
        $resource->delete();

        return response()->json(null, 204);
    }
}

