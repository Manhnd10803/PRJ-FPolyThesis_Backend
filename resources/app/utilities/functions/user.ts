import { IUser } from '@/models/user';

export const formatFullName = (user: IUser) => {
  const formatted = user?.first_name + ' ' + user?.last_name || 'NO NAME';
  // limit the length of the name to 20 characters
  return formatted.length > 20 ? formatted.slice(0, 20) + '...' : formatted;
};
