@extends('layouts.app')
@section('content')
    <style>

    </style>
    <div class="row admin-background">
        <div class="col-sm-3"></div>
        <div class="col-sm-6">
            <br><br>
            <h3>Add New Reservation</h3>
            <form action="{{ route('addNewReservation') }}" method="POST" enctype="multipart/form-data">
                @CSRF
                <div class="form-group">
                    <label for="carPlate">Car plate</label>
                    <input class="form-control" type="text" id="carPlate" name="carPlate">
                </div>
                <div class="form-group">
                    <label for="serviceType">Type of Services</label>
                    <select name="serviceType" id="serviceType" class="form-control">
                        @if ($services)
                            <option  value="{{ $services->id }}">{{ $services->name }} RM{{ $services->price }}
                            </option>
                        @endif
                    </select>
                </div>
    
    <div class="form-group">
        <label for="date">Date</label>
        <input class="form-control" type="date" id="date" name="date" max="" min="">
    </div>
    <div class="form-group">
        <label for="timeSlot">Time slot</label>
        <select name="timeSlot" id="timeSlot" class="form-control">
            <option value="1">10:00 AM</option>
            <option value="2">12:00 PM</option>
            <option value="3">2:00 PM</option>
            <option value="4">4:00 PM</option>
            <option value="5">6:00 PM</option>
        </select>
    </div>
    <div class="form-group">
        <label for="branch">Branch</label>
        <select name="branch" id="branch" class="form-control">
            @foreach ($branchs as $branch)
                <option value="{{ $branch->id }}">{{ $branch->name }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="btn btn-primary">Add New Reservation</button>
    </form>
    <br><br>
    </div>
    <div class="col-sm-3"></div>
    </div>

    <script>
        var dateRange = document.querySelector("#date");
        var date_now = new Date().getTime();
        var date_end = new Date(date_now + 2592000000); //ater one month
        var date_now = new Date(date_now + 86400000); //ater one day 

        to_YY_MM_DD = function(date) {

            let year = date.getFullYear();
            let month = date.getMonth() + 1;
            let day = date.getDate();

            month = (month < 10) ? '0' + month : month;
            day = (day < 10) ? '0' + day : day;
            return year + '-' + month + '-' + day;
        }

        var max = to_YY_MM_DD(date_end);
        var min = to_YY_MM_DD(date_now);

        dateRange.setAttribute("max", max);
        dateRange.setAttribute("min", min);

        function selectServiceType(serviceId){

            document.getElementById("").value = "4";

        }

    </script>
@endsection
