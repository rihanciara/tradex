import axios from 'axios';
import Cookies from 'js-cookie';
import { nukeAllPosData } from './db';
import { clearUserScope } from './userScope';

// The Laravel Backend API Base URL
const API_URL = process.env.NEXT_PUBLIC_API_URL || 'https://app12.dookanwale.com/api/v1';

export const apiClient = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
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

// Response interceptor — wipe ALL cached POS data before redirecting on 401.
// This is the critical security gate: if the session expires or an unauthorized
// request is made, we ensure no stale user data lingers in the browser.
apiClient.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      if (typeof window !== 'undefined' && !window.location.pathname.includes('/login')) {
        // 🔴 SECURITY: Destroy all cached POS data before logout redirect
        try {
          await nukeAllPosData();
          clearUserScope();
          console.info('[POS Security] Session expired — all cached data cleared.');
        } catch (wipeErr) {
          console.error('[POS Security] Failed to wipe data on 401:', wipeErr);
        }

        Cookies.remove('auth_token');
        window.location.href = '/login';
      }
    }
    return Promise.reject(error);
  }
);