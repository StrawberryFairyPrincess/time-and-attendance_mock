{{-- 申請一覧画面(管理者) --}}

@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin_request.css') }}" />
@endsection

@section('content')
<div class="flexbox">

    <div class="title">申請一覧</div>

    <div class="status">

        <div class="tab">

            {{-- tabを判別 --}}
            @php
                $path = request()->getRequestUri();
            @endphp

            {{-- 承認済み一覧を表示しているとき --}}
            @if( strpos($path, '?tab=done') !== false )
                <a href="/admin/requests?tab=yet"><span>承認待ち</span></a>
                <a href="/admin/requests?tab=done"><span class="checked">承認済み</span></a>
            {{-- 承認待ち一覧を表示しているとき --}}
            @else
                <a href="/admin/requests?tab=yet"><span class="checked">承認待ち</span></a>
                <a href="/admin/requests?tab=done"><span>承認済み</span></a>
            @endif

        </div>

        <div class="list">
            <table>

                {{-- 表のタイトル(header) --}}
                <tr>
                    <th>
                        状態
                    </th>
                    <th>
                        名前
                    </th>
                    <th>
                        対象日時
                    </th>
                    <th>
                        申請理由
                    </th>
                    <th>
                        申請日時
                    </th>
                    <th>
                        詳細
                    </th>
                </tr>

                @foreach( $corrections as $correction )
                    <tr>

                        {{-- 状態 --}}
                        <td>
                            @if( $correction['approve'] == '未' )
                                承認待ち
                            @elseif( $correction['approve'] == '済' )
                                承認済み
                            @endif
                        </td>

                        {{-- 名前 --}}
                        <td>
                            {{ $correction->member['name'] }}
                        </td>

                        {{-- 対象日時 --}}
                        <td>
                            {{ $correction['date']->isoFormat('YYYY/MM/DD') }}
                        </td>

                        {{-- 申請理由 --}}
                        <td>
                            {{ $correction['remarks'] }}
                        </td>

                        {{-- 申請日時 --}}
                        <td>
                            {{ $correction['created_at']->isoFormat('YYYY/MM/DD') }}
                        </td>

                        {{-- 詳細 --}}
                        <td>
                            <a href="{{ '/admin/requests/' . $correction['member_id'] . '/' . $correction['date']->isoFormat('YYYYMMDD') }}">
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