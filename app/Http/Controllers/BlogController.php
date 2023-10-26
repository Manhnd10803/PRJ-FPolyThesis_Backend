<?php

namespace App\Http\Controllers;

use App\Models\Blog;
use App\Models\Comment;
use App\Models\Like;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BlogController extends Controller
{
    public function ShowAllBlogs(Request $request)
    {
        DB::beginTransaction();
        try {
            $majorsId = $request->input('majors_id');
            $hashtag = $request->input('hashtag');
            $query = Blog::query();

            if ($majorsId) {
                $query->where('majors_id', $majorsId);
            }

            if ($hashtag) {
                $query->where('content', 'LIKE', '%' . $hashtag . '%');
            }

            $blogs = $query->latest()->get();

            $result = [];

            foreach ($blogs as $blog) {
                $likeCountsByEmotion = [];
                $likeCountsByEmotion['total_likes'] = $blog->likes->count();
                // Lấy danh sách người đã like bài viết và thông tin của họ
                $likers = $blog->likes->map(function ($like) {
                    return [
                        'user' => $like->user,
                        'emotion' => $like->emotion,
                    ];
                });
                // Tính số lượt thích cho mỗi biểu cảm (emotion)
                $emotions = $likers->pluck('emotion')->unique();
                foreach ($emotions as $emotion) {
                    $likeCountsByEmotion[$emotion] = $likers->where('emotion', $emotion)->count();
                }
                // Tính số lượt bình luận cho bài viết
                $commentCount = Comment::where('blog_id', $blog->id)->count();
                $replyCount = Comment::where('blog_id', $blog->id)->where('parent_id', '>', 0)->count();
                $totalCommentsAndReplies = $commentCount + $replyCount;

                $comments  = Comment::where('blog_id', $blog->id)->get();
                $commentsData = [];
                foreach ($comments as $comment) {
                    // Lấy thông tin người comment
                    $commentUser = $comment->user;
                    // Lấy danh sách các reply cho comment
                    $replies = Comment::where('blog_id', $blog->id)
                        ->where('parent_id', $comment->id)
                        ->get();
                    $repliesData = [];

                    foreach ($replies as $reply) {
                        // Lấy thông tin người reply
                        $replyUser = $reply->user;
                        $repliesData[] = [
                            'reply' => $reply,
                            'user' => $replyUser,
                        ];
                    }
                    $commentsData[] = [
                        'comment' => $comment,
                        'user' => $commentUser,
                        'replies' => $repliesData,
                    ];
                }
                $blogData = [
                    'blog' => $blog,
                    'like_counts_by_emotion' => $likeCountsByEmotion,
                    'total_comments' => $totalCommentsAndReplies,
                    'total_likes' => $likeCountsByEmotion['total_likes'],
                    'like' => $likers,
                    'comment' => $commentsData,
                ];
                array_push($result, $blogData);
            }
            DB::commit();
            return response()->json($result, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errors' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Post(
     *     path="/api/blogs",
     *     tags={"Blogs"},
     *     summary="Tạo blog mới",
     *     description="Tạo một blog mới",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "content", "majors_id", "thumbnail"},
     *             @OA\Property(property="title", type="string", description="Tiêu đề của blog", maxLength=255),
     *             @OA\Property(property="content", type="string", description="Nội dung của blog"),
     *             @OA\Property(property="majors_id", type="integer", description="ID của chuyên ngành liên quan đến blog"),
     *             @OA\Property(property="hashtag", type="string", description="Hashtag của blog", maxLength=255, nullable=true),
     *             @OA\Property(property="thumbnail", type="string", format="binary", description="Hình ảnh đại diện của blog (JPEG, JPG hoặc PNG)")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Blog đã được tạo thành công"),
     *     @OA\Response(response=400, description="Tệp tin không hợp lệ")
     * )
     */
    public function CreateBlog(Request $request)
    {
        DB::beginTransaction();
        try {
            $title = $request->input('title');
            $content = $request->input('content');
            $majors_id  = $request->input('majors_id');
            $hashtag  = $request->input('hashtag');
            if ($request->hasFile('thumbnail')) {
                $thumbnail = $request->file('thumbnail');
                if (!$thumbnail->isValid() || !in_array($thumbnail->getClientOriginalExtension(), ['jpg', 'jpeg', 'png'])) {
                    DB::rollBack();
                    return response()->json(['error' => 'Tệp tin không hợp lệ. Chỉ chấp nhận tệp tin JPEG, JPG hoặc PNG.'], 400);
                }
                $imagePath = time() . '_' . uniqid() . '.' . $thumbnail->extension();
                $thumbnail->move(storage_path('app/public'), $imagePath);
            }
            $blog = new Blog([
                'user_id' => Auth::id(),
                'title' => $title,
                'content' => $content,
                'thumbnail' => $imagePath,
                'majors_id' => $majors_id,
                'hashtag' => $hashtag,
                'status' => config('default.blog.status.pending'),
            ]);
            $blog->save();
            DB::commit();
            return response()->json($blog, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errors' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/blogs/{blog}",
     *     tags={"Blogs"},
     *     summary="Cập nhật thông tin blog",
     *     description="Cập nhật thông tin của một blog đã tồn tại",
     *     @OA\Parameter(
     *         name="blog",
     *         in="path",
     *         required=true,
     *         description="ID của blog cần cập nhật",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"title", "content", "majors_id", "thumbnail"},
     *             @OA\Property(property="title", type="string", description="Tiêu đề của blog", maxLength=255),
     *             @OA\Property(property="content", type="string", description="Nội dung của blog"),
     *             @OA\Property(property="majors_id", type="integer", description="ID của chuyên ngành liên quan đến blog"),
     *             @OA\Property(property="hashtag", type="string", description="Hashtag của blog", maxLength=255, nullable=true),
     *             @OA\Property(property="thumbnail", type="string", format="binary", description="Hình ảnh đại diện của blog (JPEG, JPG hoặc PNG)")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Blog đã được cập nhật thành công"),
     *     @OA\Response(response=400, description="Tệp tin không hợp lệ hoặc lỗi xử lý")
     * )
     */

    public function UpdateBlog(Request $request, Blog $blog)
    {
        DB::beginTransaction();
        try {
            $title = $request->input('title');
            $content = $request->input('content');
            $majors_id = $request->input('majors_id');
            $hashtag = $request->input('hashtag');
            if ($request->hasFile('thumbnail')) {
                $thumbnail = $request->file('thumbnail');
                if (!$thumbnail->isValid() || !in_array($thumbnail->getClientOriginalExtension(), ['jpg', 'jpeg', 'png'])) {
                    DB::rollBack();
                    return response()->json(['error' => 'Tệp tin không hợp lệ. Chỉ chấp nhận tệp tin JPEG, JPG hoặc PNG.'], 400);
                }
                $imagePath = time() . '_' . uniqid() . '.' . $thumbnail->extension();
                $thumbnail->move(storage_path('app/public'), $imagePath);
            }
            $blog->update([
                'title' => $title,
                'content' => $content,
                'thumbnail' => $imagePath,
                'majors_id' => $majors_id,
                'hashtag' => $hashtag,
            ]);
            $blog->save();
            DB::commit();
            return response()->json($blog, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errors' => $e->getMessage()], 400);
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/blogs/{blog}",
     *     tags={"Blogs"},
     *     summary="Xóa blog",
     *     description="Xóa một blog đã tồn tại",
     *     @OA\Parameter(
     *         name="blog",
     *         in="path",
     *         required=true,
     *         description="ID của blog cần xóa",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Bài blog đã bị xóa thành công", @OA\JsonContent()),
     *     @OA\Response(response=401, description="Unauthorized", @OA\JsonContent()),
     *     @OA\Response(response=403, description="Forbidden", @OA\JsonContent()),
     *     @OA\Response(response=404, description="Bài blog không tồn tại", @OA\JsonContent()),
     *     @OA\Response(response=500, description="Lỗi xảy ra khi xóa bài blog", @OA\JsonContent())
     * )
     */
    public function DeleteBlog(Blog $blog)
    {
        DB::beginTransaction();
        try {
            if (Auth::check() && Auth::user()->id === $blog->user_id) {
                Comment::where('blog_id', $blog->id)->delete();
                Like::where('blog_id', $blog->id)->delete();
                $blog->likes()->delete();
                $blog->comments()->delete();
                $blog->delete();
                DB::commit();
                return response()->json(['message' => 'Bài blog đã bị xóa thành công.'], 200);
            } else {
                DB::rollBack();
                return response()->json(['message' => 'Bạn không có quyền này']);
            }
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['errors' => $e->getMessage()], 400);
        }
    }
}
