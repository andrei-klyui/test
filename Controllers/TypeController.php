<?php

namespace App\Http\Controllers;

use App\Type;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TypeController extends Controller
{
    protected $rules = [
        'name' => ['required', 'string', 'min:2', 'max:254', 'update' => 'unique:types,name'],
    ];

    /**
     * Display a listing of the resource.
     *
     * @return Response
     */
    public function index()
    {
        $this->data = [
            'types' => Type::withCount('rooms')->sortable(['id' => 'desc'])->paginate(env('DEFAULT_PAGINATION', 20)),
        ];

        return view('type.index', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        $this->validator($request->all())->validate();
        Type::create($request->all());
        return redirect()->back()
            ->with('success', trans_choice('i.type', 1) . ' ' . __('i.created'));
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        $this->data = [
            'data' => Type::with(['rooms'])->withCount('rooms')->find($id),
        ];

        return view('type.show', $this->data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        $this->data = [
            'data' => Type::find($id),
        ];

        return view('type.edit', $this->data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        $rules = $this->rules;
        $rules['name']['update'] = "unique:types,name,$id";
        $this->validator($request->all(), $rules)->validate();
        $type = Type::find($id);
        if (!$type) {
            return redirect()->back()->with('error', __('i.row_not_found'));
        }
        $type->update($request->all());

        return redirect()->back()->with('success', __('i.info_update'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        $type = Type::withCount('rooms')->find($id);
        if ($type->rooms_count > 0) {
            return response()->json([
                'message' => __('i.not_can_delete')
            ], 403);
        } else {
            $type->delete();
            return response()->json([
                'message' => trans_choice('i.type', 1) . ' ' . __('i.deleted2')
            ]);
        }
    }
}
