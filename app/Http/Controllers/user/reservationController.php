<?php

namespace App\Http\Controllers\user;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\user\rewardController;
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
use Carbon\Carbon;

class reservationController extends Controller
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

        return view('/user/selectService')
            ->with('services', $services)
            ->with('userPackages', $userPackages);
    }

    public function viewAddReservation($id)
    {
        //select all branch data
        $branchs = branch::whereNot('status', '=', 'close')->get();

        $services = service::find($id);

        return view('/user/addReservation')
            ->with('branchs', $branchs)
            ->with('services', $services);
    }
    //go to reservation_package page
    public function viewAddReservation_package($id)
    {
        //select all branch data
        $branchs = branch::whereNot('status', '=', 'close')->get();

        return view('/user/addReservation_package')
            ->with('branchs', $branchs)
            ->with('userPackageId', $id);
    }
    //add new reservation to the database
    public function addNewReservation()
    {
        $r = request();
        $carService = $r->serviceType;
        //depend service number separate the service type name
        $serviceInformation = service::find($r->serviceType);

        $serviceName = $serviceInformation->name;
        $price = $serviceInformation->price;

        //if select normal wash
        if ($carService == '1') {
            $date = $r->date; //get the date
            $branch = $r->branch; //get the branch id
            $findSameDate1 = reservation::where([['date', '=', $date], ['Services', '=', 'Normal wash'], ['branchId', '=', $branch]])
                ->whereNot('status', '=', 'cancel')
                ->get();
            //echo $ServiceCount;
            $ServiceCount = $findSameDate1->count(); //calculate the normal wash in that time

            if ($ServiceCount > 19) {
                //if over 20 cannot booking success
                Session::flash('NormalWashOverBooking', 'Booking already full!');

                return view('user/addReservation');
            } else {
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
                $reservationId = DB::table('reservations')
                    ->where('userId', '=', Auth::id())
                    ->orderBy('created_at', 'DESC')
                    ->first();

                $reservationNumber = $reservationId->id;
                $reservationPrice = $reservationId->price;
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
                $orderId = DB::table('order_reservations')
                    ->where('userId', '=', Auth::id())
                    ->orderBy('created_at', 'DESC')
                    ->first();

                $orderNumber = $orderId->id;
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

                // $users = User::with(['stateoffice','cityoffice','hometownoffice'])->get();

                return view('/user/paymentReservation', compact('reservation', 'memberLevelDiscount'));
            }
            //if select other service
        } else {
            $date = $r->date; //get the date
            $branch = $r->branch; //get the branch id
            $findSameDate2 = reservation::where([['date', '=', $date], ['branchId', '=', $branch]])
                ->whereNot([['Services', '=', 'Normal wash'], ['status', '=', 'cancel']])
                ->get();
            $ServiceCount = $findSameDate2->count(); //calculate the normal wash in that time
            //echo $ServiceCount;
            if ($ServiceCount > 4) {
                //if over 5 cannot booking success
                Session::flash('OverBooking', 'Booking already full!');

                return view('user/addReservation');
            } else {
                //change to serviceNAME because not defined variable
                $serviceNAME = $serviceName;
                // add new reservation to database
                $addNewReservation = reservation::create([
                    'userId' => Auth::id(),
                    'branchId' => $r->branch,
                    'carPlate' => $r->carPlate,
                    'Services' => $serviceNAME,
                    'timeSlot' => $r->timeSlot,
                    'price' => $price,
                    'date' => $r->date,
                    'orderId' => '',
                    'status' => 'upcoming',
                ]);

                //get the reservation id
                $reservationId = DB::table('reservations')
                    ->where('userId', '=', Auth::id())
                    ->orderBy('created_at', 'DESC')
                    ->first();

                $reservationNumber = $reservationId->id;
                $reservationPrice = $reservationId->price;
                //select the multiple member point and then do the calculate
                $multipleNumber = memberPoint::find(1);
                $totalGetMemberPoint = $reservationPrice * $multipleNumber->multiple;

                //add new order to database
                $addNewOrder = OrderReservation::create([
                    'reservationId' => $reservationNumber,
                    'userId' => Auth::id(),
                    'paymentStatus' => 0, //(0 no payment, 1 done payment , 2 cancel payment need to refund)
                    'amount' => $reservationPrice,
                    'memberPoint' => $totalGetMemberPoint,
                    'orderPackageId' => '0',
                ]);
                
                //get the order id
                $orderId = DB::table('order_reservations')
                    ->where('userId', '=', Auth::id())
                    ->orderBy('created_at', 'DESC')
                    ->first();

                $orderNumber = $orderId->id;

                //get the reservation id
                $reservationId2 = DB::table('reservations')
                    ->where('userId', '=', Auth::id())
                    ->orderBy('created_at', 'DESC')
                    ->first();
                $reservationNumber1= $reservationId2->id;
                //update new reservation to the order id
                DB::update('update reservations set orderID = ? where id = ?', [$orderNumber, $reservationNumber1]);

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

                return view('/user/paymentReservation', compact('reservation', 'memberLevelDiscount'));
            }
        }
    }

    //view all reservation depend the user id
    public function viewMyReservation()
    {
        $checkReservationStatus = reservation::where('userId', Auth::id())
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
        (new rewardController())->calMemberLevelAndMemberPoint();

        $allReservation = DB::table('order_reservations')
            ->leftjoin('reservations', 'reservations.id', '=', 'order_reservations.reservationId')
            ->leftjoin('branches', 'branches.id', '=', 'reservations.branchId')
            ->select('order_reservations.id as orderId', 'reservations.*', 'order_reservations.paymentStatus as paymentStatus', 'branches.name as branchName')
            ->where('order_reservations.userId', '=', Auth::id())
            ->get();
        return view('/user/myReservation')->with('allReservation', $allReservation);
    }

    //display the edit reservation depend the reservation id
    public function editReservation($id)
    {
        $reservation = reservation::all()->where('id', $id);
        //get branch from the database
        $branchs = branch::whereNot('status', '=', 'close')->get();

        return view('/user/editReservation')
            ->with('reservation', $reservation)
            ->with('branchs', $branchs);
    }

    //update reservation to the database
    public function updateReservation()
    {
        //if select normal wash
        $r = request();
        $carService = $r->serviceType;

        if ($carService == 'Normal wash') {
            $date = $r->date; //get the date
            $branch = $r->branch; //get the branch id
            $findSameDate1 = reservation::where([['date', '=', $date], ['Services', '=', 'Normal wash'], ['branchId', '=', $branch]])
                ->whereNot('status', '=', 'cancel')
                ->get();
            //echo $ServiceCount;
            $ServiceCount = $findSameDate1->count(); //calculate the normal wash in that time

            if ($ServiceCount > 19) {
                //if over 20 cannot booking success
                Session::flash('NormalWashOverBooking', 'Booking already full!');
                //back to edit reservsation page
                $id = $r->reservationId;
                $reservation = reservation::all()->where('id', $id);

                return view('/user/editReservation')->with('reservation', $reservation);
            } else {
                // update exist reservation to database
                $reservation = reservation::find($r->reservationId);

                $reservation->carPlate = $r->carPlate;
                $reservation->date = $r->date;
                $reservation->timeSlot = $r->timeSlot;
                $reservation->branchId = $r->branch;
                $reservation->save();

                Session::flash('UpdateReservationSuccess', 'Upadate reservation successful!');
                return redirect()->route('viewMyReservation');
            }
            //if select other service
        } else {
            $date = $r->date; //get the date
            $branch = $r->branch; //get the branch id
            $findSameDate2 = reservation::where([['date', '=', $date], ['branchId', '=', $branch]])
                ->whereNot([['Services', '=', 'Normal wash'], ['status', '=', 'cancel']])
                ->get();
            $ServiceCount = $findSameDate2->count(); //calculate the normal wash in that time
            //echo $ServiceCount;
            if ($ServiceCount > 4) {
                //if over 5 cannot booking success
                Session::flash('NormalWashOverBooking', 'Booking already full!');
                //back to edit reservsation page
                $id = $r->reservationId;
                $reservation = reservation::all()->where('id', $id);

                return view('/user/editReservation')->with('reservation', $reservation);
            } else {
                // update exist reservation to database
                $reservation = reservation::find($r->reservationId);

                $reservation->carPlate = $r->carPlate;
                $reservation->date = $r->date;
                $reservation->timeSlot = $r->timeSlot;
                $reservation->branchId = $r->branch;
                $reservation->save();

                Session::flash('UpdateReservationSuccess', 'Upadate reservation successful!');
                return redirect()->route('viewMyReservation');
            }
        }
    }
    //cancel the reservation
    public function cancelReservation($id)
    {
        $findOrderReservation = OrderReservation::where('reservationId', $id)->first();
        if ($findOrderReservation->paymentStatus == 1) {
            //update reservation to cancel
            $reservation = reservation::find($id);
            $reservation->status = 'cancel';
            $reservation->save();

            //update payment status to 2 (0 no payment, 1 done payment , 2 cancel payment need to refund, 3 done refund)
            DB::update('update order_reservations set paymentStatus = ? where reservationId = ?', [2, $id]);

            return redirect()->route('viewMyReservation');
        }else{

            //update reservation to cancel
            $reservation = reservation::find($id);
            $reservation->status = 'cancelByPackage';
            $reservation->save();

            //update payment status to 2 (0 no payment, 1 done payment , 2 cancel payment need to refund 3 done refund )
            DB::update('update order_reservations set paymentStatus = ? where reservationId = ?', [5, $id]);

            return redirect()->route('viewMyReservation');
        }
    }

    //no done the payment ,want to pay one more
    public function repayment($orderId)
    {
        $reservation = DB::table('reservations')
            ->leftjoin('branches', 'branches.id', '=', 'reservations.branchId')
            ->select('branches.name as branchName', 'reservations.*')
            ->where('reservations.orderId', '=', $orderId)
            ->get();

        $userMemberPoint = userMemberPoint::where('userId', '=', Auth::id())->first();
        $memberLevel = $userMemberPoint->memberLevel;

        $memberLevelDiscount = memberLevel::where('memberLevel', '=', $memberLevel)->first();

        return view('/user/paymentReservation', compact('reservation', 'memberLevelDiscount'));

        //->with('reservation', $reservation)->with('memberLevelDiscount', $memberLevelDiscount);
    }

    public function addNewReservation_package()
    {
        $r = request();
        $carService = $r->serviceType;
        //depend service number separate the service type name
        $serviceInformation = service::find($r->serviceType);

        $serviceName = $serviceInformation->name;
        $price = $serviceInformation->price;

        //if select normal wash
        if ($carService == '1') {
            $date = $r->date; //get the date
            $branch = $r->branch; //get the branch id
            $findSameDate1 = reservation::where([['date', '=', $date], ['Services', '=', 'Normal wash'], ['branchId', '=', $branch]])
                ->whereNot('status', '=', 'cancel')
                ->get();
            //echo $ServiceCount;
            $ServiceCount = $findSameDate1->count(); //calculate the normal wash in that time

            if ($ServiceCount > 19) {
                //if over 20 cannot booking success
                Session::flash('NormalWashOverBooking', 'Booking already full!');

                return view('user/addReservation');
            } else {
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
                $reservationId = DB::table('reservations')
                    ->where('userId', '=', Auth::id())
                    ->orderBy('created_at', 'DESC')
                    ->first();

                $reservationNumber = $reservationId->id;
                $reservationPrice = $reservationId->price;
                //select the multiple member point and then do the calculate
                $multipleNumber = memberPoint::find(1);
                $totalGetMemberPoint = $reservationPrice * $multipleNumber->multiple;

                //add new order to database
                $addNewOrder = OrderReservation::create([
                    'reservationId' => $reservationNumber,
                    'userId' => Auth::id(),
                    'paymentStatus' => 4, //(0 no payment, 1 done payment , 2 cancel payment need to refund, 3 refund success 4 is package order reservation)
                    'amount' => $reservationPrice,
                    'memberPoint' => $totalGetMemberPoint,
                    'orderPackageId' => $r->orderPackageId,
                ]);

                //get the order id
                $orderId = DB::table('order_reservations')
                    ->where('userId', '=', Auth::id())
                    ->orderBy('created_at', 'DESC')
                    ->first();

                $orderNumber = $orderId->id;
                //update new reservation to the order id
                DB::update('update reservations set orderID = ? where id = ?', [$orderNumber, $reservationNumber]);

                //<---calculate the member point--->
                //price change to memberpoint
                $memberpoint = $reservationPrice;
                //get the multiple value in database
                $multipleNumber = memberPoint::find(1);
                //calculate the total member point
                $totalGetMemberPoint = $memberpoint * $multipleNumber->multiple;

                //find the usermemberpoitn information
                $userMemberPoint = userMemberPoint::where('userId', Auth::id())->first();

                //$totalMemberPoint = userMemberPoint::select('totalPoint')->where('userId','=',)->first();
                //total member point plus this payment total get member point
                $plusTotalMemberPoint = $userMemberPoint->totalPoint + $totalGetMemberPoint;
                //current member point plus this payment total get member point
                $plusCurrentMemberpoint = $userMemberPoint->currentPoint + $totalGetMemberPoint;

                //update to the database new member point
                $userMemberPoint->totalPoint = $plusTotalMemberPoint;
                $userMemberPoint->currentPoint = $plusCurrentMemberpoint;
                $userMemberPoint->save();
                //<---calculate the member point end--->

                //<--minus the package wash times-->
                //get the user order package id
                $orderPackageId = $r->orderPackageId;
     
                $userOrderPackage = userPackageSubscription::where('id', $orderPackageId)->first();

                $MinusWashTimes = $userOrderPackage->times - 1;

                $userOrderPackage->times = $MinusWashTimes;
                $userOrderPackage->save();

                //<--minus the package wash times end-->
                return redirect()->route('viewMyReservation');
            }
            //if select other service
        }
    }
}
