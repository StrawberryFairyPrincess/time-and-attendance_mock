{{-- 勤怠詳細画面(管理者) --}}

@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin_detail.css') }}" />
@endsection

@section('content')
<div class="flexbox">

    <div class="title">勤怠詳細</div>

    <div class="detail">
        <table>

            <tr>
                <th>
                    名前
                </th>

                <td>
                    {{ $member['name'] }}
                </td>
            </tr>

            <tr>
                <th>
                    日付
                </th>

                <td>
                    {{ $date->isoFormat('YYYY年') }}
                    <span class="space">　　</span>
                    {{ $date->isoFormat('MM月DD日') }}
                </td>
            </tr>

            <form action="{{ '/admin/attendances/' . $member['id'] . '/' . $date->isoFormat('YYYYMMDD') }}" method="POST">
                @csrf

            <tr>
                <th>
                    出勤・退勤
                </th>

                <td>
                    {{-- 出勤 --}}
                    @if( $clocks->where( 'status', '出勤' )->first() != NULL )
                        <input type="text" name="clockin" value="{{ $clocks->where( 'status', '出勤' )->first()['clock']->format('H:i') }}" />
                    @else
                        <input type="text" name="clockin" value="00:00" />
                    @endif

                    <span class="space">〜</span>

                    {{-- 退勤 --}}
                    {{-- この日に退勤のデータがあるとき --}}
                    @if( $clocks->where( 'status', '退勤' )->first() != NULL )
                        {{-- 退勤の時刻が出勤の時刻より後のとき --}}
                        @if( $clocks->where( 'status', '退勤' )->last()['clock']
                            ->isAfter( $clocks->where( 'status', '出勤' )->last()['clock'] ) )
                            <input type="text" name="clockout" value="{{ $clocks->where( 'status', '退勤' )->last()['clock']->format('H:i') }}" />
                            <input type="hidden" name="realout" value="{{ $clocks->where( 'status', '退勤' )->last()['clock']->format('H:i') }}">
                        {{-- 退勤は打刻していても前日の出勤に対する退勤の時 --}}
                        @else
                            {{-- 翌日に退勤のデータがあるとき --}}
                            @if( $tomorrows->where( 'status', '退勤' )->first() != NULL )
                                <?php
                                    $d = $tomorrows->where( 'status', '退勤' )->first()['clock']->format('Hi');
                                    $h = (int)str_split( $d, 2 )[0] + 24;
                                    $m = (int)str_split( $d, 2 )[1];
                                ?>
                                <input type="text" name="clockout"
                                    value="{{ $h }}:{{ sprintf("%02d", $m) }}" />
                                <input type="hidden" name="realout"
                                    value="{{ $h }}:{{ sprintf("%02d", $m) }}">
                            {{-- 翌日に退勤のデータがないとき --}}
                            @else
                                <input type="text" name="clockout" value="00:00" />
                            @endif
                        @endif
                    {{-- この日に退勤のデータがないとき --}}
                    @else
                        {{-- 翌日に退勤のデータがあるとき --}}
                        @if( $tomorrows->where( 'status', '退勤' )->first() != NULL )
                            <?php
                                $d = $tomorrows->where( 'status', '退勤' )->first()['clock']->format('Hi');
                                $h = (int)str_split( $d, 2 )[0] + 24;
                                $m = (int)str_split( $d, 2 )[1];
                            ?>
                            <input type="text" name="clockout"
                                value="{{ $h }}:{{ sprintf("%02d", $m) }}" />
                            <input type="hidden" name="realout"
                                value="{{ $h }}:{{ sprintf("%02d", $m) }}">
                        {{-- 翌日に退勤のデータがないとき --}}
                        @else
                            <input type="text" name="clockout" value="00:00" />
                        @endif
                    @endif
                </td>
            </tr>

            {{-- 休憩の回数分の行を追加 --}}
            <?php $i = 1; ?>
            @foreach( $clocks->whereIn( 'status', ['休憩入', '休憩戻'] ) as $clock )
                @if( $clock['status'] == '休憩入' )
                    <tr>
                        <th>
                            休憩{{$i}}
                        </th>

                        <td>
                            <input type="text" name="take{{$i}}" value="{{ $clock['clock']->format('H:i') }}" />

                            <span class="space">〜</span>

                    {{-- 最後のデータが休憩入のとき(最後の休憩戻がない) --}}
                    @if( $clocks->whereIn( 'status', ['休憩入', '休憩戻'] )->last()['status'] == '休憩入' && ( $clock['id'] == $clocks->whereIn( 'status', ['休憩入', '休憩戻'] )->last()['id'] ) )
                            <input type="text" name="back{{$i}}" value="00:00" />
                        </td>
                    </tr>
                    <?php $i++; ?>
                    @endif
                @elseif( $clock['status'] == '休憩戻' )
                            <input type="text" name="back{{$i}}" value="{{ $clock['clock']->format('H:i') }}" />
                        </td>
                    </tr>
                    <?php $i++; ?>
                @endif
            @endforeach
            {{-- 新しい休憩登録欄 --}}
            <tr>
                <th>
                    休憩{{$i}}
                </th>

                <td>
                    <input type="text" name="take{{$i}}" value="00:00" />
                    <span class="space">〜</span>
                    <input type="text" name="back{{$i}}" value="00:00" />
            </tr>

            <tr>
                <th>
                    備考
                </th>

                <td>
                    <textarea name="remarks"></textarea>
                </td>
            </tr>

        </table>
    </div>

    <div class="button">
        <button type="submit">修正</button></form>
    </div>

    @if( $errors->any() )
        <div class="form__error">
            @foreach( $errors->all() as $error )
                {{ $error }}</br>
            @endforeach
        </div>
    @endif

</div>
@endsection