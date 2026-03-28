import axios from 'axios';

// The Laravel Backend API Base URL
const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://fwcv3.test/api/jerryupdates/v1';

export const apiClient = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  // Ensure we send cookies for Sanctum authentication
  withCredentials: true,
});

// Response interceptor to handle unauthenticated sessions globally
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 || error.response?.status === 403) {
      // Redirect to login if token expires or Vercel mode is off
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);