<?php

namespace App\Http\Controllers\Panel;

use App\Models\Level;
use App\Models\Lesson;
use App\Models\Period;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use App\Http\Controllers\Controller;
use App\Http\Resources\LevelResource;
use App\Http\Resources\LessonResource;
use Illuminate\Support\Facades\Session;

class LessonController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Request $request)
    {

        try {
            $lessons = Lesson::query();

            if ($keyword = request('search')) {
                $lessons =  $lessons->where(function ($query) use ($keyword) {
                    $query->where('title', 'LIKE', '%' . $keyword . '%')
                        ->Orwhere('description', 'LIKE', '%' . $keyword . '%');
                });
            }
            if ($keyword = request('level_id')) {
                $lessons = $lessons->whereLevel_id($request->level_id);
            }
            if ($keyword = request('level_title')) {
                $level = Level::whereTitle($keyword)->first();
                $lessons = $lessons->whereLevelId($level->id);
            }
            return response()->json([
                'status' => true,
                'count' => $lessons->get()->count(),
                'data' => LessonResource::collection($lessons->paginate($request->input('per_page') ? $request->input('per_page') : 10)),
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'errors' => [$th->getMessage()]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(Lesson $lesson)
    {
        try {
            return response()->json([
                'status' => true,
                'data' => new LessonResource($lesson)
            ], Response::HTTP_OK);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'errors' => [$th->getMessage()]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'title' => ['required', 'string', 'max:255', 'unique:levels'],
                'description' => ['required'],
                'image' => ['required'],
                'period_id' => ['required', 'string', 'max:255'],
                'language_id' => ['required', 'string', 'max:255'],
                'level_id' => ['required', 'string', 'max:255'],
            ]);

            $media = $request->image;
            $path = is_file($request->image) ? (URL::asset('storage/' . $media->store('images', 'public'))) : $request->image;
            $data['image'] = $path;

            Lesson::create($data);

            return response()->json([
                'status' => true,
            ], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'errors' => [$th->getMessage()]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Level  $level
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Lesson $lesson)
    {
        try {
            $data = $request->validate([
                'title' => ['required', 'string', 'max:255',  Rule::unique('lessons', 'title')->ignore($lesson->id)],
                'description' => ['required'],
                'image' => ['required'],
            ]);

            $media = $request->image;
            $path = is_file($request->image) ? (URL::asset('storage/' . $media->store('images', 'public'))) : $request->image;
            $data['image'] = $path;

            $lesson->update($data);

            return response()->json([
                'status' => true,
            ], Response::HTTP_CREATED);
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'errors' => [$th->getMessage()]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Level  $level
     * @return \Illuminate\Http\Response
     */
    public function destroy(Lesson $lesson)
    {
        try {
            if (!$lesson->parts()->exists()) {
                $lesson->delete();
                return response()->json(['success' => 'delete completed']);
            } else {
                return response()->json(['failed' => 'related model exists...!']);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'errors' => [$th->getMessage()]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function changeFreeStatus(Request $request)
    {

        try {
            $lesson = Lesson::find($request->id);
            if ($lesson) {
                $lesson->freeornot == '0' ? $lesson->update(['freeornot' => '1']) : $lesson->update(['freeornot' => '0']);
                return response()->json([
                    'success' => ' با موفقیت انجام شد',
                    'status' => $lesson->freeornot
                ]);
            } else {
                return response()->json([
                    'failed' => 'درس یافت نشد'
                ]);
            }
        } catch (\Throwable $th) {
            return response()->json([
                'status' => false,
                'errors' => [$th->getMessage()]
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
