<?php

namespace App\Http\Controllers;

use App\DateTable;
use App\HistoryService;
use App\Item;
use App\Jobs\SendEmailOrder;
use App\Order;
use App\Room;
use App\Time;
use App\Token;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ItemController extends Controller
{
    use Time;

    protected $rules = [
        'order' => ['required', 'integer', 'min:1'],
        'room' => ['required', 'integer', 'min:1'],
        'players' => ['required', 'integer', 'min:2'],
        'date' => ['required', 'integer', 'min:1'],
        'time' => ['required', 'integer', 'min:1'],
    ];

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return Response
     * @throws AuthorizationException
     */
    public function store(Request $request)// todo сделать после графов
    {
        $data = $request->all();
        $rules = $this->rules;
        if (isset($data['service']) && count($data['service'])) {
            foreach ($data['service'] as $k => $s) {
                $rules['service.' . $k] = [
                    'integer',
                    'min:1'
                ];
            }
        }
        $this->validator($data, $rules)->validate();

        $order = Order::find($data['order']);
        $this->authorize('update', $order);
        $time = null;
        $date = DateTable::find($data['date']);
        if ($date) {
            if ($date->date === Carbon::now('Europe/Kiev')->toDateString()) {
                $time = $date->times()->where('time_string', '>', Carbon::now('Europe/Kiev')->format('H:i'))
                    ->where('status', 1)
                    ->find($data['time']);
            } else {
                $time = $date->times()->where('status', 1)->find($data['time']);
            }
        }
        if ($time) {
            $room = Room::with('address')->find($time->room_id);
        } else {
            return redirect()->back()->with('error', __('i.room_not_found2'));
        }
        if ($room) {
            $prices = $time->prices;
            if (isset($prices[$data['players']])) {
                $price = $prices[$data['players']]['price'];
            } else {
                return redirect()->back()->with('error', __('i.price_not_found'));
            }
            $time->update(['status' => 0]);
            $item = new Item();
            $item->fill([
                'room_id' => $time->room_id,
                'date' => $date->date,
                'time' => $time->time,
                'players' => $data['players'],
                'price' => $price,
            ]);
            $order->items()->save($item);
            if (isset($data['services']) && count($data['services'])) {
                $serviceRoom = $room->address->services()->whereIn('services.id', $data['services'])->get();
                $serviceIds = [];
                foreach ($serviceRoom as $sr) {
                    $hs = HistoryService::firstOrCreate([
                        'name' => $sr->name,
                        'price' => $sr->price,
                        'service_id' => $sr->id,
                    ]);
                    $serviceIds[] = $hs->id;
                }
                $item->services()->attach($serviceIds);
            }
            $item->calculatePrice();
            $this->updateDate($time->room_id, $date->date);
            Cache::tags([$room->id])->flush();
            $this->updateNodeTable($time->room_id);
            return redirect()->back()->with('success', __('i.room_add_to_order'));
        } else {
            return redirect()->back()->with('error', __('i.time_not_found'));
        }
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
        $item = Item::with(['room'])->find($id);
        $this->authorize('update', $item->order);
        $data = $request->only(['service', "players"]);
        $rules = $response = [];

        if (isset($data['players'])) {
            $rules = [
                'players' => ['integer'],
            ];
            $rules['players'][] = "min:" . $item->room->players_min;
            $rules['players'][] = "max:" . $item->room->players_max;
        } elseif (isset($data['service']) && count($data['service'])) {
            foreach ($data['service'] as $k => $s) {
                $rules['service.' . $k] = [
                    'integer',
                    'min:1'
                ];
            }
        }

        $this->validator($request->all(), $rules ?: ['_token' => ['required']])->validate();

        if (isset($data['players'])) {
            $players = $data['players'];
            $room = $item->room;
            $find = false;
            if ($room) {
                $date = Carbon::parse($item->date)->dayOfWeekIso;
                $holiday = $room->holidays()
                    ->with([
                        'prices' => function ($query) use ($players) {
                            return $query->where('players', $players);
                        }
                    ])->whereHas('prices', function ($query) use ($players) {
                        return $query->where('players', $players);
                    })
                    ->where('date_start', '<=', $item->date)
                    ->where('date_end', '>=', $item->date)
                    ->where('days', 'like', '%' . $date . '%')
                    ->get()->first();

                if ($holiday) {
                    $prices = $holiday->prices->filter(function ($price) use ($players) {
                        return $price->players == $players;
                    });
                    $item->price = $prices->first()->price;
                    $item->players = $players;
                    $find = true;
                }

                if (!$find) {
                    $timeline = $room->timelines()
                        ->with([
                            'prices' => function ($query) use ($players) {
                                return $query->where('players', $players);
                            }
                        ])->whereHas('prices', function ($query) use ($players) {
                            return $query->where('players', $players);
                        })
                        ->where('days', 'like', '%' . $date . '%')
                        ->get()->first();

                    if ($timeline) {
                        $prices = $timeline->prices->filter(function ($price) use ($players) {
                            return $price->players == $players;
                        });
                        $item->price = $prices->first()->price;
                        $item->players = $players;
                        $find = true;
                    }
                }
                if ($find) {
                    $response['message'] = __('i.count_players_updated');
                } else {
                    $response['message'] = __('i.price_not_found');
                }
            } else {
                $response['message'] = __('i.room_not_found');
            }
        } else {
            $serviceRoom = $item->room->address->services()->whereIn('services.id', $data['service'] ?? [])->get();
            $serviceIds = [];
            foreach ($serviceRoom as $sr) {
                $hs = HistoryService::firstOrCreate([
                    'name' => $sr->name,
                    'price' => $sr->price,
                    'service_id' => $sr->id,
                ]);
                $serviceIds[] = $hs->id;
            }
            $item->services()->sync($serviceIds);
            $response['message'] = __('i.services_updated');
        }

        $item->calculatePrice();
        $response['price'] = $item->item_price;
        $response['order_price'] = $item->order()->first()->price;

        return response()->json($response);
    }

    /**
     * @param Request $request
     * @param $id
     * @return bool
     * @throws AuthorizationException
     */
    /**
    {
    name: string,
    email: string,
    phone: string,
    sms_remind:boolean,
    room_id: number,
    date: string,
    time: number,
    players: number,
    services: Array<number>
    }
     */
    public function updateApi(Request $request, $id)
    {
        $data = json_decode($request->getContent(),true);
        $token = Token::with('user')->where('token', $data['token'])->first();
        $item = Item::with(['room'])->find($id);
        $rules = $response = [];
        if (!$token) {
            return false;
        }
        Auth::loginUsingId($token->user_id);
        $this->authorize('update', $item->order);
        $rules = [
            'players' => ['required','integer'],
            'price' => 'required',
            'name' => 'required|string',
//            'email' => 'required|email',
            'phone' => 'required|string',
            'room_id' => 'required|integer',
            'date' => 'required|string',
            'time' => 'required|integer',
            'comment' => 'string',
            'pay_status' => 'integer'
        ];
        $rules['players'][] = "min:" . $item->room->players_min;
        $rules['players'][] = "max:" . $item->room->players_max;
        if (isset($data['services'])) {
            foreach ($data['services'] as $k => $s) {
                $rules['service.' . $k] = [
                    'integer',
                    'min:1'
                ];
            }
        }
        $this->validator($data, $rules ?: ['_token' => ['required']])->validate();
        $item->update([
            'players' =>$data['players'],
//            'date' =>$data['date'],
//            'time' =>$data['time'],
            'price' =>$data['price'],
        ]);
        $item->services()->detach();
        if(isset($data['services']) && count($data['services']) > 0){
            $item->services()->attach($data['services']);
        }
        $item->calculatePrice();
        $order = $item->order;
        $order->update([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'comment' => $data['comment'],
            'pay_status' => $data['pay_status'],
            'price' => $order->items->sum('price'),
        ]);
        dispatch(new SendEmailOrder($order->id));
        Cache::tags([$item->room->id])->flush();
        $order->updateNodeTable($item->room->id);
        return response()->json(['status'=>'success']);


    }

    /**
     * @param $id
     * @return mixed
     *
     */
    public function getPrices($id)
    {
        $item = Item::with(['room'])->find($id);
        $price = [];
        if ($room = $item->room) {
            $find = false;
            $date = Carbon::parse($item->date)->dayOfWeekIso;
            $holiday = $room->holidays()
                ->with([
                    'prices'
                ])
                ->where('date_start', '<=', $item->date)
                ->where('date_end', '>=', $item->date)
                ->where('days', 'like', '%' . $date . '%')
                ->first();
            if ($holiday) {
                $price = $holiday->prices;
                $find = true;
            }
            if (!$find) {
                $timelines = $room->timelines()
                    ->with([
                        'prices'])
                    ->where('days', 'like', '%' . $date . '%')
                    ->where('time_open','<=',$item->time)
                    ->get();

                if ($timelines) {
                      foreach ($timelines as $timeline){
                          $table = [];
                              $day = (int)($date == 7 ? 0 : $date);
                              $times = [];
                              $data = [
                                  'prices' => $timeline->prices

                                      ->map(function ($price) {
                                          return [
                                              'price' => $price->price,
                                              'players' => $price->players,
                                          ];
                                      })->toArray(),
                                  'ticket_price' => $timeline->ticket_price,
                                  'time_play' => $timeline->time_play,

                              ];
                              $step = $timeline->time_play + $timeline->time_break;
                              for ($i = 0; $i < $timeline->count_game; $i++) {
                                  $time = ($i * $step) + $timeline->time_open;
                                  $data['time'] = $time;
                                  $unitTime[$time] = $time;
                                  $times[$time] = $data;
                              }

                              if (isset($table[$day])) {
                                  $table = array_replace($table[$day], $times);
                              } else {
                                  $table = $times;
                              }
                              ksort($table, SORT_NUMERIC);
                              if(isset($table[$item->time])){
                                  $price = $table[$item->time]['prices'];
                              }
                      }

                }
            }
        }
        $response['prices'] = $price;
        return $response;
    }

    /**
     * @param $id
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function service($id)
    {
        $item = Item::with(['room', 'order'])->find($id);
        $this->authorize('update', $item->order);
        $services = $item->room->address->services();

        $holiday = $item->room->holidays()
            ->where('date_start', '<=', $item->date)
            ->where('date_end', '>=', $item->date)
            ->where('days', 'like', "%" . Carbon::parse($item->date)->dayOfWeek . "%")
            ->first();

        if ($holiday) {
            $services = $services
                ->where('date_start', $holiday->date_start)
                ->where('date_end', $holiday->date_end);
        } else {
            $services = $services->whereNull('services.date_end');
        }

        $services = $services->get()->reduce(function ($acc, $service) {
            $acc[] = [
                "value" => $service->id,
                "text" => $service->name . ' (' . $service->price . ')',
            ];
            return $acc;
        }, []);

        return response()->json($services);
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
        $item = Item::with('order')->find($id);
        $this->authorize('update', $item->order);
        $order = $item->order;
        $count = $order->items()->count();
        if ($count < 2) {
            return response()->json([
                'message' => __('i.last_row_not_found')
            ], 403);
        } else {
            $item->delete();
            $order->updateDateAndPrice();
            $order->updateDate($item->room_id, $item->date);
            $order->updateNodeTable($item->room_id);
            return response()->json([
                'message' => __('i.delete_room_from_order')
            ]);
        }
    }
}
