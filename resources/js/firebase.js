import { initializeApp } from "firebase/app";
import { initializeFirestore, persistentLocalCache, persistentSingleTabManager } from "firebase/firestore";
import { getAuth, signInWithCustomToken } from "firebase/auth";

const firebaseConfig = {
    apiKey:            import.meta.env.VITE_FIREBASE_API_KEY,
    authDomain:        import.meta.env.VITE_FIREBASE_AUTH_DOMAIN,
    projectId:         import.meta.env.VITE_FIREBASE_PROJECT_ID,
    storageBucket:     import.meta.env.VITE_FIREBASE_STORAGE_BUCKET,
    messagingSenderId: import.meta.env.VITE_FIREBASE_MESSAGING_SENDER_ID,
    appId:             import.meta.env.VITE_FIREBASE_APP_ID,
};

const app = initializeApp(firebaseConfig);

const db = initializeFirestore(app, {
    localCache: persistentLocalCache({
        tabManager: persistentSingleTabManager()
    })
});

const auth = getAuth(app);

// Exchange Laravel session for a Firebase custom token and sign in
async function authenticateFirebase() {
    try {
        const res  = await fetch('/api/v1/firebase-token?user_id=1');
        const data = await res.json();
        await signInWithCustomToken(auth, data.token);
    } catch (err) {
        console.error('Firebase auth failed:', err);
    }
}

authenticateFirebase();

export { app, db, auth };
