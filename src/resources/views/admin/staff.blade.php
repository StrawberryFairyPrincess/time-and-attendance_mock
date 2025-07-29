{{-- スタッフ一覧画面(管理者) --}}

@extends('layouts.app')

@section('css')
    <link rel="stylesheet" href="{{ asset('css/admin_staff.css') }}" />
@endsection

@section('content')
<div class="flexbox">

    <div class="title">スタッフ一覧</div>

    <div class="list">

        <div class="date">
            <table>

                {{-- 表のタイトル(header) --}}
                <tr>
                    <th>
                        名前
                    </th>
                    <th>
                        メールアドレス
                    </th>
                    <th>
                        月次勤怠
                    </th>
                </tr>

                @foreach( $members as $member )
                <tr>

                    {{-- 名前 --}}
                    <td>
                        {{ $member['name'] }}
                    </td>

                    {{-- メールアドレス --}}
                    <td>
                        {{ $member['email'] }}
                    </td>

                    {{-- 月次勤怠 --}}
                    <td>
                        <a href="{{ '/admin/users/' . $member['id'] . '/attendances' }}">
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