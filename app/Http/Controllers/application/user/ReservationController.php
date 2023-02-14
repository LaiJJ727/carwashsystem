<?php

namespace App\Http\Controllers\application\user;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DateTime;
use DB;
use Session;
use App\Models\reservation;
use App\Models\OrderReservation;
use App\Models\branch;
use App\Models\service;
use App\Models\userMemberPoint;
use App\Models\memberPoint;
use App\Models\memberLevel;
use App\Models\userPackageSubscription;
use Auth;
use PDF;
use Carbon\Carbon;

class ReservationController extends Controller
{
    public function viewSelectService()
    {
        //select all branch data
        $userPackages = DB::table('user_package_subscriptions')
            ->leftjoin('package_subscriptions', 'package_subscriptions.id', '=', 'user_package_subscriptions.packageId')
            ->select('package_subscriptions.name as packageName', 'user_package_subscriptions.*')
            ->where('userId', '=', Auth::id())
            ->get();

        $services = service::all();
        $data = ['services', $services, 'userPackages', $userPackages];

        return response()->json($data, 200);
    }
    public function viewAddReservation($id)
    {
        //select all branch data
        $branchs = branch::whereNot('status', '=', 'close')->get();

        $services = service::find($id);
        $data = ['branchs', $branchs, 'services', $services];

        return response()->json($data, 200);
    }
    //go to reservation_package page
    public function viewAddReservation_package($id)
    {
        //select all branch data
        $branchs = branch::whereNot('status', '=', 'close')->get();

        $data = ['branchs', $branchs, 'userPackageId', $id];

        return response()->json($data, 200);
    }
    //view all reservation depend the user id
    public function viewMyReservation()
    {
        $checkReservationStatus = reservation::where('userId', '28')
            ->whereNot('status', 'cancel')
            ->get();
        $currentDate = Carbon::now()->format('Y-m-d');
        $currentTime = Carbon::now()->format('H:i:s');
        foreach ($checkReservationStatus as $checkDate) {
            $reservationId = $checkDate->id;
            $date = $checkDate->date;
            $time = $checkDate->timeSlot;

            //check status for tbe date
            if ($date == $currentDate) {
                if ($time == '1') {
                    $timeSlot = Carbon::parse('10:00:00')->format('H:i:s');
                } elseif ($time == '2') {
                    $timeSlot = Carbon::parse('12:00:00')->format('H:i:s');
                } elseif ($time == '3') {
                    $timeSlot = Carbon::parse('14:00:00')->format('H:i:s');
                } elseif ($time == '4') {
                    $timeSlot = Carbon::parse('16:00:00')->format('H:i:s');
                } else {
                    $timeSlot = Carbon::parse('18:00:00')->format('H:i:s');
                }
                //check time slot is before current time or not
                if ($timeSlot < $currentTime) {
                    DB::update('update reservations set status = ? where id = ?', ['expired', $reservationId]);
                }
            } elseif ($date < $currentDate) {
                DB::update('update reservations set status = ? where id = ?', ['expired', $reservationId]);
            }
        }
        //call calMemberLevelAndMemberPoint to get the new member level
        //(new rewardController())->calMemberLevelAndMemberPoint();

        $allReservation = DB::table('order_reservations')
            ->leftjoin('reservations', 'reservations.id', '=', 'order_reservations.reservationId')
            ->leftjoin('branches', 'branches.id', '=', 'reservations.branchId')
            ->select('order_reservations.id as orderId', 'order_reservations.amount as totalAmount', 'reservations.*', 'order_reservations.paymentStatus as paymentStatus', 'branches.name as branchName')
            ->where('order_reservations.userId', '=', Auth::id())
            ->get();

        return response()->json($allReservation, 200);
    }

    //add new reservation to the database
    public function addNewReservation()
    {
        $r = request();
        $carService = $r->serviceType;

        //if select normal wash
        if ($carService == '1') {
            $date = $r->date; //get the date
            $branch = $r->branch; //get the branch id
            $timeSlot = $r->timeSlot;
            $findSameTimeSlot = reservation::where([['date', '=', $date], ['Services', '=', 'Normal wash'], ['branchId', '=', $branch], ['timeSlot', '=', $timeSlot]])
                ->whereNot('status', '=', 'cancel')
                ->get();

            if ($findSameTimeSlot->count() >= 1) {
                //if over 1 cannot booking success

                //select all branch data
                $branchs = branch::whereNot('status', '=', 'close')->get();

                $services = service::find($r->serviceType);

                $response = ['message' => 'Booking already full!'];
                return response()->json($response, 400);
            } else {
                //depend service number separate the service type name
                $serviceInformation = service::find($r->serviceType);

                $serviceName = $serviceInformation->name;
                $price = $serviceInformation->price;
                // add new reservation to database
                $addNewReservation = reservation::create([
                    'userId' => Auth::id(),
                    'branchId' => $r->branch,
                    'carPlate' => $r->carPlate,
                    'Services' => $serviceName,
                    'timeSlot' => $r->timeSlot,
                    'price' => $price,
                    'date' => $r->date,
                    'orderId' => '',
                    'status' => 'upcoming',
                ]);
                //get the reservation id
                $reservationNumber = $addNewReservation->id;
                $reservationPrice = $addNewReservation->price;
                //select the multiple member point and then do the calculate
                $multipleNumber = memberPoint::find(1);
                $totalGetMemberPoint = $reservationPrice * $multipleNumber->multiple;

                //add new order to database
                $addNewOrder = OrderReservation::create([
                    'reservationId' => $reservationNumber,
                    'userId' => Auth::id(),
                    'paymentStatus' => 0, //(0 no payment, 1 done payment , 2 cancel payment need to refund, 3 refund success)
                    'amount' => $reservationPrice,
                    'memberPoint' => $totalGetMemberPoint,
                    'orderPackageId' => '0',
                ]);

                //get the order id
                $orderNumber = $addNewOrder->id;
                //update new reservation to the order id
                DB::update('update reservations set orderID = ? where id = ?', [$orderNumber, $reservationNumber]);

                //get reservation data to the payement reservation
                $reservation = DB::table('reservations')
                    ->leftjoin('branches', 'branches.id', '=', 'reservations.branchId')
                    ->select('branches.name as branchName', 'reservations.*')
                    ->where('reservations.orderId', '=', $orderNumber)
                    ->get();

                //find member point for the user
                $userMemberPoint = userMemberPoint::where('userId', '=', Auth::id())->first();
                $memberLevel = $userMemberPoint->memberLevel;
                //find the discount in target member level
                $memberLevelDiscount = memberLevel::where('memberLevel', '=', $memberLevel)->first();

                $response = ['reservation' => $reservation, 'memberLevelDiscount' => $memberLevelDiscount];

                return response()->json($response, 200);
            }
            //if select other service
        } else {
            $date = $r->date; //get the date
            $branch = $r->branch; //get the branch id
            $findSameDate2 = reservation::where([['date', '=', $date], ['branchId', '=', $branch]])
                ->whereNot([['Services', '=', 'Normal wash'], ['status', '=', 'cancel']])
                ->get();
            $ServiceCount = $findSameDate2->count(); //calculate the normal wash in that time
            if ($ServiceCount > 4) {
                //if over 5 cannot booking success

                //select all branch data
                $branchs = branch::whereNot('status', '=', 'close')->get();

                $services = service::find($r->serviceType);

                $response = ['message' => 'Booking already full!'];
                return response()->json($response, 400);
            } else {
                //depend service number separate the service type name
                $serviceInformation = service::find($r->serviceType);

                $serviceName = $serviceInformation->name;
                $price = $serviceInformation->price;

                // add new reservation to database
                $addNewReservation = reservation::create([
                    'userId' => Auth::id(),
                    'branchId' => $r->branch,
                    'carPlate' => $r->carPlate,
                    'Services' => $serviceName,
                    'timeSlot' => $r->timeSlot,
                    'price' => $price,
                    'date' => $r->date,
                    'orderId' => '',
                    'status' => 'upcoming',
                ]);

                //get the reservation id
                $reservationNumber = $addNewReservation->id;
                $reservationPrice = $addNewReservation->price;
                //select the multiple member point and then do the calculate
                $multipleNumber = memberPoint::find(1);
                $totalGetMemberPoint = $reservationPrice * $multipleNumber->multiple;

                //add new order to database
                $addNewOrder = OrderReservation::create([
                    'reservationId' => $reservationNumber,
                    'userId' => Auth::id(),
                    'paymentStatus' => 0, //(0 no payment, 1 done payment , 2 cancel payment need to refund, 3 refund success)
                    'amount' => $reservationPrice,
                    'memberPoint' => $totalGetMemberPoint,
                    'orderPackageId' => '0',
                ]);

                //get the order id
                $orderNumber = $addNewOrder->id;
                //update new reservation to the order id
                DB::update('update reservations set orderID = ? where id = ?', [$orderNumber, $reservationNumber]);

                //get reservation data to the payement reservation
                $reservation = DB::table('reservations')
                    ->leftjoin('branches', 'branches.id', '=', 'reservations.branchId')
                    ->select('branches.name as branchName', 'reservations.*')
                    ->where('reservations.orderId', '=', $orderNumber)
                    ->get();

                //find member point for the user
                $userMemberPoint = userMemberPoint::where('userId', '=', Auth::id())->first();
                $memberLevel = $userMemberPoint->memberLevel;
                //find the discount in target member level
                $memberLevelDiscount = memberLevel::where('memberLevel', '=', $memberLevel)->first();

                $response = ['reservation' => $reservation, 'memberLevelDiscount' => $memberLevelDiscount];

                return response()->json($response, 200);
            }
        }
    }
}