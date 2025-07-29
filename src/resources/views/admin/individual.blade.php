{{-- スタッフ別勤怠一覧画面(管理者) --}}

@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin_individual.css') }}" />
@endsection

@section('content')
<div class="flexbox">

    <div class="title">{{ $member['name'] }}さんの勤怠一覧</div>

    <div class="list">

        <div class="month">
            <form action="{{ '/admin/users/' . $member['id'] . '/attendances' }}" method="POST">
                @csrf
                <input type="hidden" name="monthly" value="{{ $today }}">

                <div class="month-side">
                    <img src="{{ asset('./img/arrow.png') }}" alt="arrow">
                    <input type="submit" name="sub" value="前月">
                    </input>
                </div>
                <div class="month-center">
                    <img src="{{ asset('./img/calendar.png') }}" alt="calendar">
                    {{ $today->isoFormat('YYYY/MM') }}
                </div>
                <div class="month-side">
                    <input type="submit" name="add" value="翌月">
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
                        日付
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

                {{-- DisplayControllerで取得したclocksテーブルの内容を表示 --}}
                @for( $i=1; $i<=$today->endOfMonth()->day; $i++ )
                <tr>
                    <?php $date = $today->startOfMonth()->addDays($i-1); ?>

                    {{-- 日付 --}}
                    <td>
                        {{ $date->isoFormat('MM/DD(ddd)') }}
                    </td>

                    {{-- その日のデータが存在するとき表示 --}}
                    @if( array_key_exists( $date->isoFormat('YYYY/MM/DD'), $table ) )

                    {{-- 出勤 --}}
                    <td>
                        @if( $table[$date->isoFormat('YYYY/MM/DD')]['clockin'] != null )
                            {{ $table[$date->isoFormat('YYYY/MM/DD')]['clockin']->format('H:i') }}
                        @endif
                    </td>

                    {{-- 退勤 --}}
                    <td>
                        {{-- 退勤のデータがあるとき --}}
                        @if( $table[$date->isoFormat('YYYY/MM/DD')]['clockout'] != null )
                            {{-- 出勤した日と同じとき --}}
                            @if( $table[$date->isoFormat('YYYY/MM/DD')]['clockout']
                                ->isSameDay( $table[$date->isoFormat('YYYY/MM/DD')]['clockin'] ) )
                                {{ $table[$date->isoFormat('YYYY/MM/DD')]['clockout']->format('H:i') }}
                            {{-- 出勤した日と異なるとき(翌日に退勤) --}}
                            @else
                                <?php
                                    $d = $table[$date->isoFormat('YYYY/MM/DD')]['clockout']->format('Hi');
                                    $h = (int)str_split( $d, 2 )[0] + 24;
                                    $m = (int)str_split( $d, 2 )[1];
                                ?>
                                {{ $h }}:{{ sprintf("%02d", $m) }}
                            @endif
                        @endif
                    </td>

                    {{-- 休憩 --}}
                    <td>
                        @if( $table[$date->isoFormat('YYYY/MM/DD')]['break'] != 0 )
                            {{ gmdate( "H:i", $table[$date->isoFormat('YYYY/MM/DD')]['break'] ) }}
                        @endif
                    </td>

                    {{-- 合計 --}}
                    <td>
                        @if( $table[$date->isoFormat('YYYY/MM/DD')]['sum'] != 0 )
                            {{ gmdate( "H:i", $table[$date->isoFormat('YYYY/MM/DD')]['sum'] ) }}
                        @endif
                    </td>

                    {{-- 詳細(出勤の打刻がある日だけ) --}}
                    <td>
                        @if( $table[$date->isoFormat('YYYY/MM/DD')]['clockin'] != null )
                            <a href="{{ '/admin/attendances/' . $member['id'] . '/' . $date->isoFormat('YYYYMMDD') }}">
                                詳細
                            </a>
                        @endif
                    </td>

                    @endif

                </tr>
                @endfor

            </table>
        </div>

    </div>

</div>
@endsection