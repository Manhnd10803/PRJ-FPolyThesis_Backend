import httpRequest from '../axios-instance';
import { ApiConstants } from '../endpoints';
import { TokenService } from './token.service';

type LoginResponseType = {
  // Define the properties of the response data
  id: number;
  name: string;
};
type RegisterResponseType = {
  // Define the properties of the response data
  id: number;
  name: string;
};

const Login = <T>(data: T) => {
  return httpRequest.post<LoginResponseType>(ApiConstants.LOGIN, data);
};

const Register = <T>(data: T) => {
  return httpRequest.post<RegisterResponseType>(ApiConstants.REGISTER, data);
};

const LoginWithGoogle = () => {
  fetch('http://localhost:8000/api/google-auth')
    .then(response => {
      if (response.ok) {
        return response.json();
      }
      throw new Error('Something went wrong!');
    })
    .then(({ url }) => (window.location.href = url))
    .then(response => {
      console.log(response);
      // save token to local storage
      if (response.data.accessToken) {
        TokenService.setUser(response.data);
      }
    })
    .catch(error => {
      console.error('Error:', error);
    });
};

export const AuthService = { Login, Register, LoginWithGoogle };
