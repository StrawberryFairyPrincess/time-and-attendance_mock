{{-- 勤怠一覧画面(管理者) --}}

@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin_index.css') }}" />
@endsection

@section('content')
<div class="flexbox">

    <div class="title">{{ $today->isoFormat('YYYY年MM月DD日') }}の勤怠</div>

    <div class="list">

        <div class="month">
            <form action="/admin/attendances" method="POST">
                @csrf
                <input type="hidden" name="daily" value="{{ $today }}">

                <div class="month-side">
                    <img src="{{ asset('./img/arrow.png') }}" alt="arrow">
                    <input type="submit" name="sub" value="前日">
                    </input>
                </div>
                <div class="month-center">
                    <img src="{{ asset('./img/calendar.png') }}" alt="calendar">
                    {{ $today->isoFormat('YYYY/MM/DD') }}
                </div>
                <div class="month-side">
                    <input type="submit" name="add" value="翌日">
                    </input>
                    <img class="rotate" src="{{ asset('./img/arrow.png') }}" alt="arrow">
                </div>
            </form>
        </div>

        <div class="date">
            <table>

                {{-- 表のタイトル(header) --}}
                <tr>
                    <th>
                        名前
                    </th>
                    <th>
                        出勤
                    </th>
                    <th>
                        退勤
                    </th>
                    <th>
                        休憩
                    </th>
                    <th>
                        合計
                    </th>
                    <th>
                        詳細
                    </th>
                </tr>

                @foreach( $table as $id => $row )
                <tr>

                    {{-- 名前 --}}
                    <td>
                        {{ $row['name'] }}
                    </td>

                    {{-- 出勤 --}}
                    <td>
                        {{ $row['clockin']->format('H:i') }}
                    </td>

                    {{-- 退勤 --}}
                    <td>
                        {{-- 出勤した日と同じとき --}}
                        @if( $row['clockout']->isSameDay( $row['clockin'] ) )
                            {{ $row['clockout']->format('H:i') }}
                        {{-- 出勤した日と異なるとき(翌日に退勤) --}}
                        @else
                            <?php
                                $d = $row['clockout']->format('Hi');
                                $h = (int)str_split( $d, 2 )[0] + 24;
                                $m = (int)str_split( $d, 2 )[1];
                            ?>
                            {{ $h }}:{{ sprintf("%02d", $m) }}
                        @endif
                    </td>

                    {{-- 休憩 --}}
                    <td>
                        @if( $row['break'] != 0 )
                            {{ gmdate( "H:i", $row['break'] ) }}
                        @endif
                    </td>

                    {{-- 合計 --}}
                    <td>
                        @if( $row['sum'] != 0 )
                            {{ gmdate( "H:i", $row['sum'] ) }}
                        @endif
                    </td>

                    {{-- 詳細(出勤の打刻がある日だけ) --}}
                    <td>
                        <a href="{{ '/admin/attendances/' . $id . '/' . $today->isoFormat('YYYYMMDD') }}">
                            詳細
                        </a>
                    </td>

                </tr>
                @endforeach

            </table>
        </div>

    </div>

</div>
@endsection