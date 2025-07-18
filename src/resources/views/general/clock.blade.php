{{-- 出勤登録画面(一般ユーザ) --}}

@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/general_clock.css') }}" />
@endsection

@section('content')

    <div class="flex">
        <div class="box">

            <span class="status">{{ $status }}</span></br>
            <span class="day">{{ $date->isoFormat('YYYY年MM月DD日(ddd)') }}</span></br>
            <span class="time">{{ $date->format('H:i') }}</span>

            <form action="/attendance" method="POST">
                @csrf

                @if( $status == '勤務外' )
                    <input type="submit" name="clockin" value="出勤"></input>
                @elseif( $status == '出勤中' )
                    <input type="submit" name="clockout" value="退勤"></input>
                    <input type="submit" name="break" value="休憩入"></input>
                @elseif( $status == '休憩中' )
                    <input type="submit" name="back" value="休憩戻"></input>
                @elseif( $status == '退勤済' )
                    <p class="message">お疲れ様でした</p>
                @endif
            </form>

        </div>
    </div>

@endsection