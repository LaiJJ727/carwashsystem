@extends('layouts.app')

@section('content')
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <link rel="stylesheet" type="text/css" href="../../css/Table/main.css">
    <style>
        body {
            background-color: #f5f9ff;
        }

        .tr {
            background-color: white !important;
            color: black !important;
        }

        #body {
            margin-top: 2%;
            margin-bottom: 2%;
            margin-left: 5%;
            margin-right: 5%;
            text-align: center;

        }

        @media screen and (max-width: 400px) {
            #body {
                margin-top: 2%;
                margin-bottom: 2%;
                margin-left: 0%;
                margin-right: 0%;
                text-align: center;
            }
        }

        #tablePosition {
            margin-top: 1%;
            margin-bottom: 2%;
            margin-right: 2%;
            text-align: center;


        }

        .buttonStyle {

            background-color: #3f3b4b !important;
            display: inline-block;
            color: white;
            border-radius: 8px 8px;
            font-size: 15px;
        }

        .buttonStyle:hover {
            background-color: #494949 !important;
            color: white;
        }

        @media screen and (max-width: 440px) {
            .buttonStyle {
                font-size: 12px;
            }
        }

        .nav-style {
            display: block;
            font-size: var(--bs-nav-link-font-size);
            padding: 0.5rem 1rem;
            font-weight: var(--bs-nav-link-font-weight);
            color: var(--bs-nav-link-color);
            text-decoration: none;
            transition: color .15s ease-in-out, background-color .15s ease-in-out, border-color .15s ease-in-out;
        }

        .columnPrice {
            width: 100px;
        }
        .input{
            background-color:white;

        }
    </style>
    <form action="{{ route('payment.package') }}" method="post" class="require-validation" data-cc-on-file="false"
        data-stripe-publishable-key="{{ env('STRIPE_KEY') }}"id="payment-form">
        @CSRF
        <div class="row">
            <div class="col-sm-3"></div>
            <div class="col-sm-6">
                <br><br>
                <table class="table-adjust">

                    <thead style="background-color: #3f3b4b !important; color:white;">
                        <tr>
                            <th>Package Id</th>
                            <th>Package Name</th>
                            <th>Wash Times</th>
                            <th class="columnPrice">Price</th>

                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($packageInformation as $package)
                            <tr>
                                <input type="hidden" name="id" value="{{ $orderPackageId }}">
                                <td class="tr">{{ $package->id }}</td>
                                <td class="tr">{{ $package->name }}</td>
                                <td class="tr">{{ $package->washTimes }}</td>
                                <td class="tr columnPrice">{{ number_format($package->price, 2) }}<input type="text"
                                        value="{{ $package->price }}" name="price" id="price" size="7"
                                        hidden /></td>
                            </tr>
                        @endforeach
                        <tr>
                            @if ($memberLevelDiscount)
                                <td class="tr"></td>
                                <td class="tr"></td>
                                <td class="tr">Discount {{ $memberLevelDiscount->discount }}% <input type="text"
                                        name="discountRate" id="discountRate" size="1"
                                        value="{{ $memberLevelDiscount->discount }}" hidden /></td>
                                <td class="tr columnPrice"><input type="text" value="0" name="discountAmount"
                                        id="discountAmount" size="7" readonly /></td>
                            @else
                                <input type="text" name="discountRate" id="discountRate" size="1" value="0"
                                    hidden />
                            @endif
                        </tr>
                        <tr>
                            <td class="tr"></td>
                            <td class="tr"></td>
                            <td class="tr">Total Price:</td>
                            <td class="tr columnPrice"> <input type="text" value="0" name="totalAmount"
                                    id="totalAmount" size="7" readonly /></td>
                            <p id="demo"></p>

                        </tr>
                    </tbody>

                </table>
            </div>
            <div class="col-sm-3">

            </div>
            <script>
                var dicount = document.getElementById("discountRate");
                if (dicount.value != "0") {
                    var price = document.getElementById("price");
                    var discountPrice = 0;
                    var totalDicountPrice = 0;
                    var totalAmount = 0;
                    var dicount = document.getElementById("discountRate");

                    discountPrice = parseFloat(dicount.value) / 100;

                    totalDicountPrice = parseFloat(price.value) * parseFloat(discountPrice);


                    document.getElementById("discountAmount").value = "-" + totalDicountPrice.toFixed(2); //convert 2 decimal place


                    totalAmount = parseFloat(price.value) - parseFloat(totalDicountPrice.toFixed(2));

                    document.getElementById('totalAmount').value = totalAmount.toFixed(2); //convert 2 decimal place
                } else {
                    var price = document.getElementById("price");
                    document.getElementById('totalAmount').value = price.value; //convert 2 decimal place

                }
            </script>
        </div>

        <div class="row">
            <div class="col-sm-2"></div>
            <div class="col-sm-10"></div>
        </div>
        <div class="row">
            <div class="col-sm-3"></div>
            <br>
            <div class="col-md-6 col-md-offset-3">
                <div class="panel panel-default credit-card-box">
                    <div class="panel-heading">
                        <div class="row" style="margin-top:3%;">
                            <h3 style="text-align:center">Card Payment</h3>

                        </div>
                    </div>
                    <div class="panel-body">

                        <br>

                        <div class='form-row row'>
                            <div class='col-xs-12 col-md-6 form-group required'>
                                <label class='control-label'>Name on Card</label>
                                <input class='form-control input' size='4' type='text'>
                            </div>
                            <div class='col-xs-12 col-md-6 form-group required'>
                                <label class='control-label'>Card Number</label>
                                <input autocomplete='off' class='form-control card-number input' size='20' type='text'>
                            </div>
                        </div>
                        <div class='form-row row'>
                            <div class='col-xs-12 col-md-4 form-group cvc required'>
                                <label class='control-label'>CVC</label>
                                <input autocomplete='off' class='form-control card-cvc input' placeholder='ex. 311' size='4'
                                    type='text'>
                            </div>
                            <div class='col-xs-12 col-md-4 form-group expiration required'>
                                <label class='control-label'>Expiration Month</label>
                                <input class='form-control card-expiry-month input' placeholder='MM' size='2'
                                    type='text'>
                            </div>
                            <div class='col-xs-12 col-md-4 form-group expiration required'>
                                <label class='control-label'>Expiration Year</label>
                                <input class='form-control card-expiry-year input' placeholder='YYYY' size='4'
                                    type='text'>
                            </div>
                        </div>
                        {{-- <div class='form-row row'>
                        <div class='col-md-12 error form-group hide'>
                        <div class='alert-danger alert'>Please correct the errors and try
                            again.
                        </div>
                        </div>
                    </div> --}}
                        <div class="form-row row">
                            <div class="col-xs-12">
                                <button class="buttonStyle btn-lg btn-block" type="submit">Pay Now</button>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
    </form>
    </div>

    <script type="text/javascript" src="https://js.stripe.com/v2/"></script>

    <script type="text/javascript">
        $(function() {

            /*------------------------------------------
            --------------------------------------------
            Stripe Payment Code
            --------------------------------------------
            --------------------------------------------*/

            var $form = $(".require-validation");

            $('form.require-validation').bind('submit', function(e) {
                var $form = $(".require-validation"),
                    inputSelector = ['input[type=email]', 'input[type=password]',
                        'input[type=text]', 'input[type=file]',
                        'textarea'
                    ].join(', '),
                    $inputs = $form.find('.required').find(inputSelector),
                    $errorMessage = $form.find('div.error'),
                    valid = true;
                $errorMessage.addClass('hide');

                $('.has-error').removeClass('has-error');
                $inputs.each(function(i, el) {
                    var $input = $(el);
                    if ($input.val() === '') {
                        $input.parent().addClass('has-error');
                        $errorMessage.removeClass('hide');
                        e.preventDefault();
                    }
                });

                if (!$form.data('cc-on-file')) {
                    e.preventDefault();
                    Stripe.setPublishableKey($form.data('stripe-publishable-key'));
                    Stripe.createToken({
                        number: $('.card-number').val(),
                        cvc: $('.card-cvc').val(),
                        exp_month: $('.card-expiry-month').val(),
                        exp_year: $('.card-expiry-year').val()
                    }, stripeResponseHandler);
                }

            });

            /*------------------------------------------
            --------------------------------------------
            Stripe Response Handler
            --------------------------------------------
            --------------------------------------------*/
            function stripeResponseHandler(status, response) {
                if (response.error) {
                    $('.error')
                        .removeClass('hide')
                        .find('.alert')
                        .text(response.error.message);
                } else {
                    /* token contains id, last4, and card type */
                    var token = response['id'];

                    $form.find('input[type=text]').empty();
                    $form.append("<input type='hidden' name='stripeToken' value='" + token + "'/>");
                    $form.get(0).submit();
                }
            }

        });
    </script>
@endsection
