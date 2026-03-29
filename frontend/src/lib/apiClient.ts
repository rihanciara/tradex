import axios from 'axios';
import Cookies from 'js-cookie';

// The Laravel Backend API Base URL
const API_URL = process.env.NEXT_PUBLIC_API_URL || 'http://fwcv3.test/api/jerryupdates/v1';

export const apiClient = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
  // Ensure we send cookies for Sanctum authentication
  // Make sure CORS supports_credentials is true in Laravel
  withCredentials: true,
});

// Request interceptor to add token if it exists (Passport fallback)
apiClient.interceptors.request.use((config) => {
  if (typeof window !== 'undefined') {
    const token = Cookies.get('auth_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
  }
  return config;
});

// Response interceptor to handle unauthenticated sessions globally
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401 || error.response?.status === 403) {
      // Redirect to login if token expires or Vercel mode is off
      if (typeof window !== 'undefined' && window.location.pathname !== '/login') {
        Cookies.remove('auth_token');
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  }
);