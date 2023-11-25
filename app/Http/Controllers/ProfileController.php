<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Comment;
use App\Models\Post;
use App\Models\Qa;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Stmt\Break_;

class ProfileController extends Controller
{

    public function DetailProfileUser(User $user)
    {
        DB::beginTransaction();
        try {
            // $user = Auth::user();
            $countposts = $user->posts->count();
            $countblogs  = $user->blogs->count();
            $countfriends = $user->friends->count();
            $major = $user->major;
            $profileData = [
                'user' => [
                    'id' => $user->id,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'avatar' => $user->avatar,
                    'major' => $major->majors_name,
                    'cover_photo' => $user->cover_photo,
                ],
                'total_post' => $countposts,
                'total_blog' => $countblogs,
                'total_friend' => $countfriends,
            ];
            DB::commit();
            return response()->json($profileData);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }


    public function Profile(User $user, $type, $status = null)
    {
        DB::beginTransaction();
        try {
            $loggedInUser = Auth::user();
            $result = [];
            switch ($type) {
                    // Lấy post với friend và images
                case 'post':
                    // Lấy danh sách bạn bè của người dùng
                    $friends = $user->friends;
                    $friendDetails = [];

                    // Duyệt qua danh sách bạn bè để lấy thông tin ID, firstname, lastname và avatar
                    foreach ($friends as $friend) {
                        $friendInfo = [
                            'id' => $friend->id,
                            'first_name' => $friend->first_name,
                            'last_name' => $friend->last_name,
                            'avatar' => $friend->avatar,
                        ];
                        // Thêm thông tin của người bạn vào mảng friendDetails
                        $friendDetails[] = $friendInfo;
                    }
                    // Lấy danh sách bài viết của người dùng
                    $postsQuery = Post::where('user_id', $user->id)->orderBy('created_at', 'DESC');
                    if ($user->id != $loggedInUser->id) {
                        $postsQuery->where('status', 0);
                    }
                    $images = [];
                    $postList = $postsQuery->get();
                    foreach ($postList as $post) {
                        $postImages = $post->image;
                        if (!is_null($postImages) && is_array($postImages)) {
                            foreach ($postImages as $image) {
                                $images[] = $image;
                            }
                        } elseif (!is_null($postImages) && is_string($postImages)) {
                            if ($postImages == "null") {
                                continue;
                            }
                            $images[] = $postImages;
                        }
                    }
                    $allImageUrls = [];
                    foreach ($images as $imageString) {
                        // Xử lý chuỗi JSON để lấy danh sách URL hình ảnh
                        $imageArray = json_decode($imageString);

                        // Kiểm tra nếu là một chuỗi JSON hợp lệ
                        if (json_last_error() === JSON_ERROR_NONE && is_array($imageArray)) {
                            foreach ($imageArray as $imageUrl) {
                                // Xử lý các đường dẫn hình ảnh và thêm vào mảng tất cả các đường dẫn
                                $imageUrl = str_replace(['\\"', '"'], ['', ''], $imageUrl); // Loại bỏ ký tự \ và "
                                $allImageUrls[] = $imageUrl;
                            }
                        } else {
                            // Nếu không phải là chuỗi JSON, xử lý trực tiếp URL và thêm vào mảng
                            $imageUrl = str_replace(['\\"', '"'], ['', ''], $imageString); // Loại bỏ ký tự \ và "
                            $allImageUrls[] = $imageUrl;
                        }
                    }

                    // Lấy 9 URL đầu tiên từ danh sách
                    $firstNineImages = array_slice($allImageUrls, 0, 9);
                    $posts = $postsQuery->paginate(10);
                    $listPost = [];
                    foreach ($posts as $post) {

                        // Lấy tất cả like của bài viết
                        $likeCountsByEmotion = [];
                        $likeCountsByEmotion['total_likes'] = $post->likes->count();
                        $likers = $post->likes->map(function ($like) {
                            return [
                                'user' => $like->user,
                                'emotion' => $like->emotion,
                            ];
                        });
                        $emotions = $likers->pluck('emotion')->unique();
                        $emotionLikeCounts = [];
                        foreach ($emotions as $emotion) {
                            $emotionLikeCounts[$emotion] = $likers->where('emotion', $emotion)->count();
                        }
                        // Tổng số bình luận + 3 bình luận demo
                        $totalComment = Comment::where('post_id', $post->id)->count();
                        $commentDemos = Comment::where('post_id', $post->id)->where('parent_id', 0)->limit(3)->get();
                        foreach ($commentDemos as $commentDemo) {
                            $commentDemo->user;
                            // Số lượng reply
                            $commentDemo->reply = Comment::where('post_id', $post->id)->where('parent_id', $commentDemo->id)->count();
                        }
                        $postData = [
                            'post' => $post,
                            'like_counts_by_emotion' => $likeCountsByEmotion,
                            'emotion_like_counts' => $emotionLikeCounts,
                            'total_comments' => $totalComment,
                            'democomments' => $commentDemos,
                        ];
                        $listPost[] = $postData;
                    }
                    $detailTimeline = [
                        'user' => [
                            'id' => $user->id,
                            'first_name' => $user->first_name,
                            'last_name' => $user->last_name,
                            'avatar' => $user->avatar,
                        ],
                        'friend_details' => $friendDetails,
                        'images' => $firstNineImages,
                    ];
                    DB::commit();
                    return response()->json(['listPost' => $listPost, 'detailTimeline' => $detailTimeline], 200);
                    break;
                    // Load blog
                case 'blog':
                    $status = request('status') ?? 'approved';
                    $statuses = config('default.blog.status');
                    if (in_array($status, array_keys($statuses))) {
                        // Xử lý logic cho trường hợp 'blog' dựa trên trạng thái (pending , approved , reject)
                        $statusValue = $statuses[$status];
                        $query = Blog::where('user_id', $loggedInUser->id)->where('status', $statusValue)->orderBy('created_at', 'DESC');
                        $blogs = $query->paginate(10);
                        if ($blogs->isEmpty()) {
                            $message = "Không có blog với trạng thái $status";
                            return response()->json(['data' => [], 'message' => $message], 200);
                        }
                        $blogData = [
                            'blog' => $blogs,
                        ];
                        array_push($result, $blogData);
                    } else {
                        return response()->json(['error' => 'Trạng thái không hợp lệ'], 400);
                    }
                    break;
                    // Load qa
                case 'qa':
                    $qas = Qa::where('user_id', $loggedInUser->id)->orderBy('created_at', 'DESC')->paginate(10);
                    if ($qas->isEmpty()) {
                        return response()->json(['data' => [], 'message' => 'Không có bài viết'], 200);
                    }
                    $qaData = [
                        'qa' => $qas,
                    ];
                    array_push($result, $qaData);
                    break;
                case 'commentedQuestions':
                    $commentedQas = Comment::select('qa_id')
                        ->where('user_id', $loggedInUser->id)
                        ->whereNotNull('qa_id')
                        ->with(['qa' => function ($query) {
                            $query->select('id', 'title');
                        }])
                        ->orderBy('qa_id')
                        ->orderBy('created_at', 'ASC')
                        ->paginate(10);

                    $groupedQas = $commentedQas->groupBy('qa_id');
                    $uniqueQas = $groupedQas->map(function ($items) {
                        return [
                            'qa_id' => $items->first()->qa_id,
                            'title' => $items->first()->qa->title,
                            'updated_at' => $items->first()->qa->updated_at,
                        ];
                    });

                    $qaData = [
                        'pagination' => [
                            'current_page' => $commentedQas->currentPage(),
                            'per_page' => $commentedQas->perPage(),
                            'total' => $commentedQas->total(),
                            'last_page' => $commentedQas->lastPage(),
                        ],
                        'data' => $uniqueQas->values()->all(),
                    ];

                    $data = [
                        'qa' => $qaData
                    ];
                    array_push($result, $data);
                    break;
            }
            DB::commit();
            return response()->json(['data' => $result], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
    public function UpdateAvatarForUser(Request $request)
    {
        $user = Auth::user();
        $avatar = $request->input('avatar');
        $user->avatar = $avatar;
        $user->update();
        $post = new Post([
            'user_id' => $user->id,
            'content' => " ",
            'image' => json_encode($avatar),
        ]);
        $post->save();
        return response()->json(['data' => [$user, $post], 'message' => 'Thêm ảnh đại diện thành công'], 200);
    }
    public function UpdateCoverPhotoForUser(Request $request)
    {
        $user = Auth::user();
        $cover_photo = $request->input('cover_photo');
        $user->cover_photo = $cover_photo;
        $user->update();
        $post = new Post([
            'user_id' => $user->id,
            'content' => " ",
            'image' => json_encode($cover_photo),
        ]);
        $post->save();
        return response()->json(['data' => [$user, $post], 'message' => 'Thêm ảnh đại diện thành công'], 200);
    }
    public function updateProfile(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = Auth::user();

            // Retrieve input data
            $inputData = $request->except('password');

            if ($request->input('avatar')) {

                $inputData['avatar'] = $request->input('avatar');
            } else {
                // Nếu không có tệp tin avatar mới, giữ nguyên giá trị avatar hiện có
                $inputData['avatar'] = $user->avatar;
            }

            $user->update($inputData);
            DB::commit();
            return response()->json($user, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getInfoUser()
    {
        try {
            $user = Auth::user();
            if (!$user) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }
            return response()->json(['user' => $user], 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }
}
