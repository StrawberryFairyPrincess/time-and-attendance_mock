{{-- 修正申請承認画面(管理者) --}}

@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin_approve.css') }}" />
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

            <form action="{{ '/admin/requests/' . $member['id'] . '/' . $date->isoFormat('YYYYMMDD') }}" method="POST">
                @csrf

            <tr>
                <th>
                    出勤・退勤
                </th>

                <td>
                    {{-- 修正申請の履歴がないか承認済みのとき --}}
                    @if( $correction == NULL || $correction['approve'] == '済' )
                        {{ $clocks->where( 'status', '出勤' )->first()['clock']->format('H:i') }}
                    {{-- 修正申請の履歴があるけど未承認のとき --}}
                    @elseif( $correction != NULL && $correction['approve'] == '未' )
                        {{-- 申請履歴があって出勤時刻の申請があるとき --}}
                        @if( $correction['clockin'] != NULL )
                            {{ $correction['clockin']->format('H:i') }}
                        {{-- 申請履歴があるけど出勤時刻の申請はしていないとき --}}
                        @else
                            {{ $clocks->where( 'status', '出勤' )->first()['clock']->format('H:i') }}
                        @endif
                    @endif

                    <span class="space">〜</span>

                    {{-- この日に退勤のデータがあるとき --}}
                    @if( $clocks->where( 'status', '退勤' )->first() != NULL )
                        {{-- 退勤の時刻が出勤の時刻より後のとき --}}
                        @if( $clocks->where( 'status', '退勤' )->last()['clock']
                            ->isAfter( $clocks->where( 'status', '出勤' )->last()['clock'] ) )
                            {{-- 修正申請の履歴がないか承認済みのとき --}}
                            @if( $correction == NULL || $correction['approve'] == '済' )
                                {{ $clocks->where( 'status', '退勤' )->last()['clock']->format('H:i') }}
                            {{-- 修正申請の履歴があるけど未承認のとき --}}
                            @elseif( $correction != NULL && $correction['approve'] == '未' )
                                {{-- 申請履歴があって退勤時刻の申請があるとき --}}
                                @if( $correction['clockout'] != NULL )
                                    {{-- 申請時刻が当日のとき --}}
                                    @if( $date->isSameDay( $correction['clockout'] ) )
                                        {{ $correction['clockout']->format('H:i') }}
                                    {{-- 申請時刻が翌日のとき --}}
                                    @else
                                        {{ (int)explode( ':', $correction['clockout']->format('H:i') )[0] + 24 }}:{{ explode( ':', $correction['clockout']->format('H:i') )[1] }}
                                    @endif
                                    <input type="hidden" name="realout"
                                        value="{{ $clocks->where( 'status', '退勤' )
                                            ->last()['clock']->format('Y-m-d H:i:s') }}">
                                {{-- 申請履歴があるけど退勤時刻の申請はしていないとき --}}
                                @else
                                    {{ $clocks->where( 'status', '退勤' )->last()['clock']->format('H:i') }}
                                @endif
                            @endif

                        {{-- 退勤は打刻していても前日の出勤に対する退勤の時 --}}
                        @else
                            {{-- 翌日に退勤のデータがあるとき --}}
                            @if( $tomorrows->where( 'status', '退勤' )->first() != NULL )
                                <?php
                                    $d = $tomorrows->where( 'status', '退勤' )->first()['clock']->format('Hi');
                                    $h = (int)str_split( $d, 2 )[0] + 24;
                                    $m = (int)str_split( $d, 2 )[1];
                                ?>
                                {{-- 修正申請の履歴がないか承認済みのとき --}}
                                @if( $correction == NULL || $correction['approve'] == '済' )
                                    {{ $h }}:{{ sprintf("%02d", $m) }}
                                {{-- 修正申請の履歴があるけど未承認のとき --}}
                                @elseif( $correction != NULL && $correction['approve'] == '未' )
                                    {{-- 申請履歴があって退勤時刻の申請があるとき --}}
                                    @if( $correction['clockout'] != NULL )
                                        {{-- 申請時刻が当日のとき --}}
                                        @if( $date->isSameDay( $correction['clockout'] ) )
                                            {{ $correction['clockout']->format('H:i') }}
                                        {{-- 申請時刻が翌日のとき --}}
                                        @else
                                            {{ (int)explode( ':', $correction['clockout']->format('H:i') )[0] + 24 }}:{{ explode( ':', $correction['clockout']->format('H:i') )[1] }}
                                        @endif
                                        <input type="hidden" name="realout"
                                            value="{{ $tomorrows->where( 'status', '退勤' )
                                                ->first()['clock']->format('Y-m-d H:i:s') }}">
                                    {{-- 申請履歴があるけど退勤時刻の申請はしていないとき --}}
                                    @else
                                        {{ $h }}:{{ sprintf("%02d", $m) }}
                                    @endif
                                @endif

                            {{-- 翌日に退勤のデータがないとき --}}
                            @else
                                {{-- 修正申請の履歴がないか承認済みのとき --}}
                                @if( $correction == NULL || $correction['approve'] == '済' )
                                {{-- 修正申請の履歴があるけど未承認のとき --}}
                                @elseif( $correction != NULL && $correction['approve'] == '未' )
                                    {{-- 申請履歴があって退勤時刻の申請があるとき --}}
                                    @if( $correction['clockout'] != NULL )
                                        {{-- 申請時刻が当日のとき --}}
                                        @if( $date->isSameDay( $correction['clockout'] ) )
                                            {{ $correction['clockout']->format('H:i') }}
                                        {{-- 申請時刻が翌日のとき --}}
                                        @else
                                            {{ (int)explode( ':', $correction['clockout']->format('H:i') )[0] + 24 }}:{{ explode( ':', $correction['clockout']->format('H:i') )[1] }}
                                        @endif
                                    @endif
                                @endif
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
                            {{-- 修正申請の履歴がないか承認済みのとき --}}
                            @if( $correction == NULL || $correction['approve'] == '済' )
                                {{ $h }}:{{ sprintf("%02d", $m) }}"
                            {{-- 修正申請の履歴があるけど未承認のとき --}}
                            @elseif( $correction != NULL && $correction['approve'] == '未' )
                                {{-- 申請履歴があって退勤時刻の申請があるとき --}}
                                @if( $correction['clockout'] != NULL )
                                    {{-- 申請時刻が当日のとき --}}
                                    @if( $date->isSameDay( $correction['clockout'] ) )
                                        {{ $correction['clockout']->format('H:i') }}
                                    {{-- 申請時刻が翌日のとき --}}
                                    @else
                                        {{ (int)explode( ':', $correction['clockout']->format('H:i') )[0] + 24 }}:{{ explode( ':', $correction['clockout']->format('H:i') )[1] }}
                                    @endif
                                    <input type="hidden" name="realout"
                                        value="{{ $tomorrows->where( 'status', '退勤' )
                                            ->first()['clock']->format('Y-m-d H:i:s') }}">
                                {{-- 申請履歴があるけど退勤時刻の申請はしていないとき --}}
                                @else
                                    {{ $h }}:{{ sprintf("%02d", $m) }}
                                @endif
                            @endif

                        {{-- 翌日に退勤のデータがないとき --}}
                        @else
                            {{-- 修正申請の履歴がないか承認済みのとき --}}
                            @if( $correction == NULL || $correction['approve'] == '済' )
                            {{-- 修正申請の履歴があるけど未承認のとき --}}
                            @elseif( $correction != NULL && $correction['approve'] == '未' )
                                {{-- 申請履歴があって退勤時刻の申請があるとき --}}
                                @if( $correction['clockout'] != NULL )
                                    {{-- 申請時刻が当日のとき --}}
                                    @if( $date->isSameDay( $correction['clockout'] ) )
                                        {{ $correction['clockout']->format('H:i') }}
                                    {{-- 申請時刻が翌日のとき --}}
                                    @else
                                        {{ (int)explode( ':', $correction['clockout']->format('H:i') )[0] + 24 }}:{{ explode( ':', $correction['clockout']->format('H:i') )[1] }}
                                    @endif
                                @endif
                            @endif
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
                            {{-- 休憩入 --}}
                            {{-- 修正申請の履歴がないか承認済みのとき --}}
                            @if( $correction == NULL || $correction['approve'] == '済' )
                                {{ $clock['clock']->format('H:i') }}
                            {{-- 修正申請の履歴があるけど未承認のとき --}}
                            @elseif( $correction != NULL && $correction['approve'] == '未' )
                                {{-- 申請履歴があって休憩入時刻の申請があるとき --}}
                                @if( $correction['breaks']['take' . $i] != NULL )
                                    {{ explode( ':', explode( ' ', $correction['breaks']['take' . $i] )[1] )[0] }}:{{ explode( ':', explode( ' ', $correction['breaks']['take' . $i] )[1] )[1] }}
                                {{-- 申請履歴があるけど休憩入時刻の申請はしていないとき --}}
                                @else
                                    {{ $clock['clock']->format('H:i') }}
                                @endif
                            @endif

                            <span class="space">〜</span>

                    {{-- 休憩戻 --}}
                    {{-- 最後のデータが休憩入のとき(最後の休憩戻がない) --}}
                    @if( $clocks->whereIn( 'status', ['休憩入', '休憩戻'] )->last()['status'] == '休憩入' && ( $clock['id'] == $clocks->whereIn( 'status', ['休憩入', '休憩戻'] )->last()['id'] ) )
                        {{-- 修正申請の履歴がないか承認済みのとき --}}
                        @if( $correction == NULL || $correction['approve'] == '済' )
                        {{-- 修正申請の履歴があるけど未承認のとき --}}
                        @elseif( $correction != NULL && $correction['approve'] == '未' )
                            {{-- 申請履歴があって休憩戻の申請があるとき --}}
                            @if( $correction['breaks']['back' . $i] != NULL )
                                {{ explode( ':', explode( ' ', $correction['breaks']['back' . $i] )[1] )[0] }}:{{ explode( ':', explode( ' ', $correction['breaks']['back' . $i] )[1] )[1] }}
                            @endif
                        @endif
                        </td>
                    </tr>
                    <?php $i++; ?>
                    @endif
                @elseif( $clock['status'] == '休憩戻' )
                            {{-- 修正申請の履歴がないか承認済みのとき --}}
                            @if( $correction == NULL || $correction['approve'] == '済' )
                                {{ $clock['clock']->format('H:i') }}
                            {{-- 修正申請の履歴があるけど未承認のとき --}}
                            @elseif( $correction != NULL && $correction['approve'] == '未' )
                                {{-- 申請履歴があって休憩戻時刻の申請があるとき --}}
                                @if( $correction['breaks']['back' . $i] != NULL )
                                    {{ explode( ':', explode( ' ', $correction['breaks']['back' . $i] )[1] )[0] }}:{{ explode( ':', explode( ' ', $correction['breaks']['back' . $i] )[1] )[1] }}
                                {{-- 申請履歴があるけど休憩戻時刻の申請はしていないとき --}}
                                @else
                                    {{ $clock['clock']->format('H:i') }}
                                @endif
                            @endif
                        </td>
                    </tr>
                    <?php $i++; ?>
                @endif
            @endforeach

            {{-- 修正申請の履歴があるけど未承認で、新しい休憩の申請があるとき --}}
            @if( $correction != NULL && $correction['approve'] == '未' &&
                ( isset( $correction['breaks']['take' . $i] ) ||
                    isset( $correction['breaks']['back' . $i] ) ) )
                <tr>
                    <th>
                        休憩{{$i}}
                    </th>

                    <td>
                        {{-- 新しい休憩入の申請があるとき --}}
                        @if( isset( $correction['breaks']['take' . $i] ) )
                            {{ explode( ':', explode( ' ', $correction['breaks']['take' . $i] )[1] )[0] }}:{{ explode( ':', explode( ' ', $correction['breaks']['take' . $i] )[1] )[1] }}
                        @endif

                        <span class="space">〜</span>

                        {{-- 新しい休憩戻の申請があるとき --}}
                        @if( isset( $correction['breaks']['back' . $i] ) )
                            {{ explode( ':', explode( ' ', $correction['breaks']['back' . $i] )[1] )[0] }}:{{ explode( ':', explode( ' ', $correction['breaks']['back' . $i] )[1] )[1] }}
                        @endif
                </tr>

            @endif

            <tr>
                <th>
                    備考
                </th>

                <td>
                    {{-- 修正申請の履歴がないか承認済みのとき --}}
                    @if( isset($correction) )
                        {{ $correction['remarks'] }}
                    @endif
                </td>
            </tr>

        </table>
    </div>

    {{-- 承認済みのとき --}}
    @if( $correction != NULL && $correction['approve'] == '済' )
        <div class="button">
            <button class="done" type="button" disabled>承認済み</button>
        </div>
    {{-- 未承認のとき --}}
    @elseif( $correction != NULL && $correction['approve'] == '未' )
        <div class="button">
            <button class="yet" type="submit">承認</button>
        </div>
    @endif
    </form>

</div>
@endsection