import { FriendService } from '@/apis/services/friend.service';
import { StorageFunc } from '@/utilities/local-storage/storage-func';
import { useQuery, useQueryClient } from '@tanstack/react-query';

type activityFriend = {
  user_id: number;
  activity_user: string;
};

const queryKeyFriends = ['friends'];

export const fetchAllFriendMyUser = async () => {
  const idUser = StorageFunc.getUserId();
  const { data } = await FriendService.showAllFriendMyUser(idUser);
  return data;
};

export const useFriend = () => {
  const { data, ...rest } = useQuery({
    queryKey: queryKeyFriends,
    queryFn: fetchAllFriendMyUser,
    staleTime: 1000 * 60 * 5, // 5 minutes
  });
  return {
    data,
    ...rest,
  };
};

export const useSetListFriend = () => {
  const queryClient = useQueryClient();

  const manuallySetListFriend = (action: string, data: any) => {
    queryClient.setQueryData(queryKeyFriends, (oldData: any) => {
      if (!oldData) return oldData;

      if (action === 'add') {
        console.log(data.id);
        return [...oldData, data];
      }
      if (action === 'delete') {
        console.log(data.id);
        const updatedData = oldData.filter((item: any) => item.id !== data.id);
        return updatedData;
      }
      return oldData;
    });
  };
  return {
    manuallySetListFriend,
  };
};

export const useSetActivityFriend = () => {
  const queryClient = useQueryClient();

  const manuallySetActivityFriend = (data: activityFriend) => {
    queryClient.setQueryData(queryKeyFriends, (oldData: any) => {
      if (!oldData) return oldData;
      console.log(oldData);
      const updatedData = oldData.map((item: any) => {
        if (item.user_id_2 === data.user_id) {
          return { ...item, friend: { ...item.friend, activity_user: data.activity_user } };
        }
        return item;
      });

      return updatedData;
    });
  };

  return {
    manuallySetActivityFriend,
  };
};
