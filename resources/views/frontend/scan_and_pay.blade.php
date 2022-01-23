@extends('frontend.layouts.app')
@section('title', 'Scan & Pay')
@section('content')
<div class="scan-and-pay">
    <div class="card my-card">
        <div class="card-body text-center">
            @include('frontend.layouts.flash')

            <div class="text-center">
                <img src="{{asset('img/scan-and-pay.png')}}" alt="" style="width: 220px;">
            </div>
            <p class="mb-3">Click button, put QR code in the frame and pay.</p>
            <button class="btn btn-theme btn-sm" data-toggle="modal" data-target="#scanModal">Scan</button>

            <!-- Scan Modal -->
            <div class="modal fade" id="scanModal" tabindex="-1" aria-labelledby="scanModalLabel"
                aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="exampleModalLabel">Scan & Pay</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body">
                            <video id="scanner" width="100%" height="240px"></video>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('scripts')
<script src="{{asset('frontend/js/qr-scanner.umd.min.js')}}"></script>
<script>
    $(document).ready(function(){
        var videoElem = document.getElementById('scanner');
        const qrScanner = new QrScanner(videoElem, function(result){
            if(result){
                qrScanner.stop();
                $('#scanModal').modal('hide');

                var to_phone = result;
                window.location.replace(`scan-and-pay-form?to_phone=${to_phone}`);
            }
        });


        $('#scanModal').on('shown.bs.modal', function (event) {
            qrScanner.start();
        });

        $('#scanModal').on('hidden.bs.modal', function (event) {
            qrScanner.stop();
        });

    });
</script>
@endsection
