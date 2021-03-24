<?php

namespace App\Http\Controllers;

use App\Item;
use App\Jobs\SendEmailFeedback;
use App\Jobs\SendSmsRemind;
use App\Notification;
use App\Order;
use App\Room;
use App\User;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use SoapClient;
use Spatie\DbDumper\Databases\MySql;

class TestController extends Controller
{
    static function createRemindSmsAfterTwenty()
    {
        $now = Carbon::now();
        $item = new Item();

        $time = $item->toMinute($now->format('H:i'));

        $items = Item::whereHas('order', function ($query) {
            return $query->where('status', Order::CONFIRM);
        })
            ->where('date', $now->toDateString())
            ->where('time', $time)
            ->get();

        if ($items) {
            foreach ($items as $item) {
                $job = (new SendSmsRemind($item->order->id, $item->id))
                    ->delay(Carbon::today()->addHours(3)->addMinutes($time));
                dispatch($job);
            }
        }
    }

    static function saveEmailTemplate(Notification $notf)
    {
        $path = resource_path('views') . '/email/' . $notf->id . '.blade.php';
        File::replace($path, $notf->body);
    }

    public function index()
    {
        $now = Carbon::now();

        $items = Item::whereHas('order', function ($query) {
            return $query->where('status', Order::CONFIRM);
        })
            ->where('date', $now->toDateString())
            ->get();

        if ($items) {
            foreach ($items as $item) {
                dump($item->time);
//                $job = (new SendSmsRemind($item->order->id, $item->id))
//                    ->delay(Carbon::today()->addHours(9));
//                dispatch($job);
            }
        }


        dd($items);
    }

    function index2($order_id = 58)
    {
        echo '<pre>';
        try {

            // Подключаемся к серверу
            $client = new SoapClient(env('URL_TURBO_SMS'));

            $order = Order::with('items', 'items.room')
                ->where('sms_confirm', 1)
                ->where('status', Order::CONFIRM)
                ->find($order_id);
            $notf = Notification::sms()->where('status', 1)->find(env('SMS_CONFIRM', 0));

            $data = [
                'user_name' => $order->name,
                'url' => route('order.fo.user', $order->hash),
            ];


            if ($order && $notf) {
                $text = view('sms.' . $notf->id, $data)->render();
                $url = route('order.fo.user', $order->hash);

                $auth = [
                    'login' => env('TURBO_LOGIN', ''),
                    'password' => env('TURBO_PASSWORD', ''),
                ];
                $result = $client->Auth($auth);
                dump($text);
                dump($result);
//                dump($url);

                $sms = [
//                    'sender' => env('SMS_NAME', 'izolyatsia'), //max:11
                    'sender' => 'Msg', //max:11
                    'destination' => '+380631182138',
                    'text' => $text,
//                    'wappush' => $url
                ];
//                $result = $client->SendSMS($sms);
                dump($result);


                print_r($text);
                var_dump($text);
                dump($text);


                echo 'order №' . $order_id . ' sms confirm ' . PHP_EOL;
            } elseif ($notf) {
                echo 'order №' . $order_id . ' Not found (sms-confirm) ' . PHP_EOL;
            } else {
                echo 'Notification  template #' . env('SMS_CONFIRM', 0) . ' Not found ' . PHP_EOL;
            }


//            dump($order);
//            dump($notf);


//
//            // Можно просмотреть список доступных методов сервера
            print_r($client->__getFunctions());

            // Данные авторизации


            dump($auth);
            // Авторизируемся на сервере
            $result = $client->Auth($auth);

            // Результат авторизации
            echo $result->AuthResult . PHP_EOL;

            // Получаем количество доступных кредитов
            $result = $client->GetCreditBalance();
            echo $result->GetCreditBalanceResult . PHP_EOL;
//
//            // Текст сообщения ОБЯЗАТЕЛЬНО отправлять в кодировке UTF-8
//            $text = iconv('windows-1251', 'utf-8', 'Это сообщение будет доставлено на указанный номер');
//
//            // Отправляем сообщение на один номер.
//            // Подпись отправителя может содержать английские буквы и цифры. Максимальная длина - 11 символов.
//            // Номер указывается в полном формате, включая плюс и код страны
//            $sms = [
//                'sender' => 'Rassilka',
//                'destination' => '+380XXXXXXXXX',
//                'text' => $text
//            ];
//            $result = $client->SendSMS($sms);
//
//            // Отправляем сообщение на несколько номеров.
//            // Номера разделены запятыми без пробелов.
//            $sms = [
//                'sender' => 'Rassilka',
//                'destination' => '+380XXXXXXXX1,+380XXXXXXXX2,+380XXXXXXXX3',
//                'text' => $text
//            ];
//            $result = $client->SendSMS($sms);
//
//            // Выводим результат отправки.
//            echo $result->SendSMSResult->ResultArray[0] . PHP_EOL;
//
//            // ID первого сообщения
//            echo $result->SendSMSResult->ResultArray[1] . PHP_EOL;
//
//            // ID второго сообщения
//            echo $result->SendSMSResult->ResultArray[2] . PHP_EOL;
//
//            // Отправляем сообщение с WAPPush ссылкой
//            // Ссылка должна включать http://
//            $sms = [
//                'sender' => 'Rassilka',
//                'destination' => '+380XXXXXXXXX',
//                'text' => $text,
//                'wappush' => 'http://super-site.com'
//            ];
//            $result = $client->SendSMS($sms);
//
//            // Запрашиваем статус конкретного сообщения по ID
//            $sms = ['MessageId' => 'c9482a41-27d1-44f8-bd5c-d34104ca5ba9'];
//            $status = $client->GetMessageStatus($sms);
//            echo $status->GetMessageStatusResult . PHP_EOL;

        } catch (Exception $e) {
            echo 'Ошибка: ' . $e->getMessage() . PHP_EOL;
        }
        echo '</pre>';

    }

    public function test($id)
    {
        $endpoint = env('NODE_HOST') . ":" . env('NODE_PORT') . "/room/$id";
//        $endpoint = "https://www.google.com.ua/?hl=ru";
        $client = new Client();

        $response = $client->request('GET', $endpoint);

// url will be: http://my.domain.com/test.php?key1=5&key2=ABC;

        $statusCode = $response->getStatusCode();
        $content = $response->getBody();

        dump($statusCode);
        dump($content);
    }

    public function test2()
    {
        $users = User::all();
        foreach ($users as $user) {
            $user->updateToken();
        }

    }

    public function test3()
    {
        MySql::create()
            ->setDumpBinaryPath('/usr/bin/')
            ->setDbName(env('DB_DATABASE'))
            ->setUserName(env('DB_USERNAME'))
            ->setPassword(env('DB_PASSWORD'))
            ->dumpToFile('dump.sql');

    }

    public function test4()
    {
        $id = 1;
        $room = Room::find($id);
        if ($room) {
            $room->updatePrice();
            $room->check();
            $room->updateDates($id);
            Cache::tags($id)->flush();
            $room->updateNodeTable($id);
        }
        return 4;
    }

    public function send2()
    {
        $order_id = 36;
        $order = Order::with('items', 'items.room', 'items.room.address', 'items.room.city')
            ->where('email_confirm', 1)
            ->where('status', Order::CONFIRM)
            ->find($order_id);
        $notf = Notification::email()->where('status', 1)->find(env('EMAIL_CONFIRM', 0));

        if ($order && $notf) {
            $rooms = [];
            foreach ($order->items as $item) {
                $rooms[] = [
                    'name' => $item->room->name ?? '',
                    'city' => $item->room->city->name ?? '',
                    'address' => $item->room->address->name ?? '',
                    'date' => Carbon::parse($item->date)->format('d.m.Y') . ' ' . $order->toTime($item->time),
                ];
            }

            $data = [
                'order_id' => $order->id,
                'order_url' => route('order.fo.user', [$order->hash]),
                'user_name' => $order->name,
                'rooms' => $rooms,
            ];

            Mail::send('email.' . $notf->id, $data, function ($mail) use ($order, $notf) {
                $mail->to($order->email, $order->name)->subject($notf->name);
            });
            echo 'order №' . $order_id . ' confirm ' . PHP_EOL;
        } elseif ($notf) {
            echo 'order №' . $order_id . ' Not found ' . PHP_EOL;
        } else {
            echo 'Notification  template #' . env('EMAIL_CONFIRM', 0) . ' Not found ' . PHP_EOL;
        }
    }

    public function testA()
    {

//        $stream = fopen("php://output", "w");
//        Artisan::call("snapshot:list", array(), new StreamOutput($stream));
//
//        dump($stream);

        $files = Storage::disk('snapshots')->allFiles('/');
        unset($files[0]);
        $files = array_slice(array_reverse($files), 20);
        $count = count($files);

        if ($count) {
            Storage::disk('snapshots')->delete($files);
        }


//        Storage::delete(['file.jpg', 'file2.jpg']);


//        $data = null;
//        dump(Artisan::call('snapshot:list', [], $data));
        dump($files);
    }

    public function testemail($city_id)
    {
//        dispatch(new SendEmailConfirm(75));
        dispatch(new SendEmailFeedback(75));

//
//
//        $city = City::with('settings')->find($city_id);
//        $settings = $city->settings()->get()->keyBy('key')->map(function ($setting) {
//            return $setting->value;
//        })->toArray();
//
//
////MAIL_HOST=smtp.beget.com
////MAIL_PORT=465
////MAIL_USERNAME=bh@lineup.com.ua
////MAIL_PASSWORD=05210190bB
////MAIL_ENCRYPTION=ssl
////
////MAIL_FROM_ADDRESS=bh@lineup.com.ua
////MAIL_FROM_NAME=Lord
//
//
//        $data = [
//            'port' => $settings['mail.port'] ?? 465,
//            'host' => $settings['mail.host'] ?? 'smtp.gmail.com',
//            'username' => $settings['mail.username'] ?? '',
//            'password' => $settings['mail.password'] ?? '',
//            'name' => $settings['mail.name'] ?? '',
//            'email' => $settings['mail.email'] ?? '',
//        ];
//        dump($data);
//
//
//        $backup = Mail::getSwiftMailer();
//        $transport = new Swift_SmtpTransport($settings['mail.host'] ?? 'smtp.gmail.com', $settings['mail.port'] ?? 465,
//            'ssl');
//        $transport->setUsername($settings['mail.username'] ?? '');
//        $transport->setPassword($settings['mail.password'] ?? '');
//
//        Config::set('mail.from', ['address' => $settings['mail.email'] ?? '', 'name' => $settings['mail.name'] ?? '']);
//
//        $gmail = new Swift_Mailer($transport);
//        Mail::setSwiftMailer($gmail);
//
//        Mail::send('city.email', [], function ($mail) {
//            $mail->to('bohdanuk.hr@gmail.com', 'sdfgfdsg')->subject('test');
//        });
//
//        Mail::setSwiftMailer($backup);
//
//        dd($settings);

    }
}
