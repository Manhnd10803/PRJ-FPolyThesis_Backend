@extends('admin.layouts.app')
@section('title')
    Danh sách bài post
@endsection
@section('content')
    @push('css')
        <link rel="stylesheet" href="{{ asset('bower_components/datatables.net-bs/css/dataTables.bootstrap.min.css') }}">
        <style>
            .example-modal .modal {
                position: relative;
                top: auto;
                bottom: auto;
                right: auto;
                left: auto;
                display: block;
                z-index: 1;
            }

            .example-modal .modal {
                background: transparent !important;
            }
        </style>
    @endpush
    <div class="row">
        <div class="col-xs-12 mx-5">
            <div class="box">
                <div class="box-header">
                    <h3 class="box-title">Danh sách bài post</h3>
                </div>
                <div class="box-body">

                    <table id="example1" class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>ID</th>
                                <th>Tên người dùng</th>
                                <th>Nội dung</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($posts as $index => $post)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $post->id }}</td>
                                    <td>
                                        @if ($post->user && $post->user->username)
                                            {{ $post->user->username }}
                                        @else
                                            Chưa có
                                        @endif
                                    </td>
                                    <td>{{ $post->content }}</td>
                                    <td>
                                        <a href="{{ route('admin.posts.show', $post->id) }}" class="btn btn-info btn-sm"><i
                                                class="fa fa-eye"></i></a>
                                        @if ($post->status == config('default.post.status.approved'))
                                            <button type="submit" class="btn btn-danger btn-sm" data-toggle="modal"
                                                data-target="#modal-danger-{{ $post->id }}"><i
                                                    class="fa fa-trash-o"></i></button>
                                        @endif

                                        <div class="modal modal-danger fade" id="modal-danger-{{ $post->id }}">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <button type="button" class="close" data-dismiss="modal"
                                                            aria-label="Close">
                                                            <span aria-hidden="true">&times;</span></button>
                                                        <h4 class="modal-title">FpolyZone</h4>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p>Bạn có chắc muốn xóa bài viết "ID: {{ $post->id }}"? </p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-outline pull-left"
                                                            data-dismiss="modal">Hủy</button>
                                                        <form action="{{ route('admin.posts.destroy', $post->id) }}"
                                                            method="POST" style="display:inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-outline">Đồng ý</button>
                                                        </form>
                                                    </div>
                                                </div>
                                                <!-- /.modal-content -->
                                            </div>
                                            <!-- /.modal-dialog -->
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>



    @push('js')
        <script src="{{ asset('bower_components/datatables.net/js/jquery.dataTables.min.js') }}"></script>
        <script src="{{ asset('bower_components/datatables.net-bs/js/dataTables.bootstrap.min.js') }}"></script>
        <script>
            $(function() {
                $('#example1').DataTable()
                $('#example2').DataTable({
                    'paging': true,
                    'lengthChange': false,
                    'searching': false,
                    'ordering': true,
                    'info': true,
                    'autoWidth': false
                })
            })
        </script>
    @endpush
@endsection
