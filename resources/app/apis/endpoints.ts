export const ApiConstants = {
  // auth
  LOGIN: '/auth/login',
  REFRESH_TOKEN: '/auth/refresh',

  LOGIN_GOOGLE: '/auth/google-auth',
  REGISTER: '/auth/register',
  FORGOT_PASSWORD: '/auth/post-forgot-password',
  RESET_PASSWORD: '/auth/post-reset-password',
  CONFIRM_PASSWORD: '/auth/confirm-password',
  RESET_NEW_PASSWORD: '/auth/reset-new-password',
  LOGOUT: '/auth/logout',
  VERIFY_EMAIL_REGISTER: '/auth/verify',

  // user
  USER: '/users',
  USER_DETAIL: '/get-user',

  // question and answer
  QANDA: '/quests',
  ALL_QANDA: '/quests/all',
  // LIST_QANDA: '/quests/lista',
  LIST_QANDA_BY_MAJOR: '/quests/major',
  LIST_MY_QANDA: '/quests/my-quests',
  LIST_MOST_CMT_QANDA: '/quests/most-commented',
  LIST_UNANSWER_QANDA: '/quests/unanswer',
  DETAIL_QANDA: '/quests/detail',

  // post
  POSTS: '/posts',
  GET_POSTS_NEW_FEED: `/posts/newfeed`,

  // blog
  BLOGS: '/blogs',
  BLOG_DETAIL: '/blogs/detail',

  // major
  MAJORS: '/majors',
  LIST_MAJORS_REGISTER: '/list-majors',

  // Comment
  COMMENT: '/comment',
  // Like
  CREATE_LIKE_BLOG: '/like/blog',
  CREATE_LIKE_QANDA: '/like/qa',

  // Friend
  SHOW_FRIEND_REQUEST: '/friend-list-request',
  CONFIRM_FRIEND_REQUEST: '/confirm-request',
  DELETE_FRIEND_REQUEST: '/delete-request',
  SHOW_FRIEND_MY_USER: `friend-list`,
  ADD_FRIEND: `send-request`,
  STATUS_FRIEND: `status-friend`,
  UN_FRIEND: `unfriend`,
  LIST_SUGGEST_FRIEND: `/friend-suggest`,

  // profile
  PROFILE: '/profile',
  UPDATE_COVER_PHOTO: '/profile/update-cover-photo',

  //Info User
  SHOW_INFO_USER: `user-info`,
  EDIT_INFO_USER: `profile/update`,

  // chat
  LIST_USER_CHAT: '/messages/list-user',
  CHAT_CHANNEL: '/messages/channel',

  MESSAGES: '/messages',

  NOTIFICATIONS: '/notifications',
} as const;
