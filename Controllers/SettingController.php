<?php

namespace App\Http\Controllers;

use App\City;
use App\Setting;
use App\User;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\Factory;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Swift_Mailer;
use Swift_SmtpTransport;

class SettingController extends Controller
{
    protected $rules = [
        'key' => ['string', 'max:254', 'min:4', 'nullable'],
        'value' => ['string', 'max:254', 'nullable'],
        'description' => ['string', 'max:254', 'nullable'],
        'city_id' => ['required', 'integer'],
    ];

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return Factory|View
     * @throws AuthorizationException
     */
    public function index(Request $request)
    {
        $this->authorize('viewAny', Setting::class);
        $settings = Setting::with(['city', "user"]);
        $cities = [];

        if ($request->user()->role === User::CITY) {
            $settings = $settings->where('city_id', $request->user()->city_id);
        } else {
            $cities = City::All();
        }

        $settings = $settings->sortable(['is_default' => 'desc'])
            ->paginate(env('DEFAULT_PAGINATION', 20));

        $this->data = [
            'settings' => $settings,
            'cities' => $cities,
        ];

        return view('setting.index', $this->data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     * @throws AuthorizationException
     */
    public function store(Request $request)
    {
        $this->authorize('create', Setting::class);
        $rules = $this->rules;
        $city_id = $request->get('city_id');
        $rules['key']['create'] = Rule::unique('settings')->where(function ($query) use ($city_id) {
            return $query->where('city_id', $city_id);
        });
        $this->validator($request->all(), $rules)->validate();
        Setting::create($request->all());

        return redirect(route('setting.index'))
            ->with('success', trans_choice('i.setting', 1) . ' ' . __('i.created'));
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @param Request $request
     * @return Response
     * @throws AuthorizationException
     */
    public function show(Request $request, $id)
    {
        $setting = Setting::with(['city', "user:id,name"])->find($id);
        $this->authorize('view', $setting);
        $cities = [];

        if ($request->user()->role !== User::CITY) {
            $cities = City::All();
        }

        $this->data = [
            'data' => $setting,
            'cities' => $cities,
        ];

        return view('setting.show', $this->data);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @param Request $request
     * @return Response
     * @throws AuthorizationException
     */
    public function edit(Request $request, $id)
    {
        $setting = Setting::with(['city', "user:id,name"])->find($id);
        $this->authorize('update', $setting);
        $cities = [];

        if ($request->user()->role !== User::CITY) {
            $cities = City::All();
        }

        $this->data = [
            'data' => $setting,
            'cities' => $cities,
        ];

        return view('setting.edit', $this->data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param int $id
     * @return Response
     * @throws AuthorizationException
     */
    public function update(Request $request, $id)
    {
        $setting = Setting::find($id);
        $this->authorize('update', $setting);

        $city_id = $request->get('city_id');
        $rules = $this->rules;
        $rules['name']['update'] = Rule::unique('rooms')->where(function ($query) use ($city_id) {
            return $query->where('city_id', $city_id);
        })->ignore($setting->id);
        $this->validator($request->all(), $rules)->validate();
        $setting->update($request->all([
            'key',
            'value',
            'description',
            'city_id',
        ]));

        return redirect(route('setting.index'))->with('success', __('i.info_update'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return Response
     * @throws AuthorizationException
     */
    public function destroy($id)
    {
        $setting = Setting::find($id);

        $this->authorize('delete', $setting);
        $setting->delete();
        return response()->json([
            'message' => trans_choice('i.setting', 1) . ' ' . __('i.deleted2')
        ]);
    }

    public function testEmail($city_id, Request $request)
    {
        $user = Auth::user();
        if (
            $user->role === User::LORD ||
            $user->role === User::SUPERADMIN ||
            ($user->role === User::CITY && $user->city_id === $city_id)
        ) {
            $city = City::with('settings')->find($city_id);
            $settings = $city->settings()->get()->keyBy('key')->map(function ($setting) {
                return $setting->value;
            })->toArray();

            try {
                $backup = Mail::getSwiftMailer();
                $transport = new Swift_SmtpTransport($settings['mail.host'] ?? 'smtp.gmail.com',
                    $settings['mail.port'] ?? 465, 'tls');
                $transport->setUsername($settings['mail.username'] ?? '');
                $transport->setPassword($settings['mail.password'] ?? '');

                Config::set('mail.from',
                    ['address' => $settings['mail.email'] ?? '', 'name' => $settings['mail.name'] ?? '']);

                $gmail = new Swift_Mailer($transport);
                Mail::setSwiftMailer($gmail);

                Mail::send('city.email', [], function ($mail) use ($request) {
                    $mail->to($request->get('email'), '')->subject('test');
                });

                Mail::setSwiftMailer($backup);
                $data = [
                    'status' => 200,
                    'message' => __('i.check_you_email')
                ];
            } catch (Exception $exception) {
                $data = [
                    'status' => 500,
                    'message' => __('i.message_not_can_send'),
                    'setting' => $settings,
                    'errors' => $exception->getMessage()
                ];
            }
        } else {
            $data = [
                'status' => 403,
                'message' => __('i.not_have_permissions')
            ];
        }
        return response()->json($data);
    }
}
