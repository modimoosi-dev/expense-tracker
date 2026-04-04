package com.expensetracker.bw;

import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.util.Base64;
import android.webkit.MimeTypeMap;
import android.webkit.WebSettings;

import com.getcapacitor.BridgeActivity;

import java.io.InputStream;

public class MainActivity extends BridgeActivity {

    private String pendingBase64   = "";
    private String pendingMimeType = "";

    @Override
    public void onCreate(Bundle savedInstanceState) {
        registerPlugin(CsvIntentPlugin.class);
        super.onCreate(savedInstanceState);

        // Remove the "wv" WebView marker from the User-Agent.
        WebSettings settings = getBridge().getWebView().getSettings();
        String ua = settings.getUserAgentString();
        settings.setUserAgentString(ua.replace("; wv", "").replace(" wv", ""));

        handleIncomingIntent(getIntent());
    }

    @Override
    protected void onNewIntent(Intent intent) {
        super.onNewIntent(intent);
        handleIncomingIntent(intent);
    }

    private void handleIncomingIntent(Intent intent) {
        if (intent == null) return;
        String action = intent.getAction();
        Uri uri = intent.getData();

        if (uri == null && Intent.ACTION_SEND.equals(action)) {
            uri = intent.getParcelableExtra(Intent.EXTRA_STREAM);
        }

        if (uri != null && (Intent.ACTION_VIEW.equals(action) || Intent.ACTION_SEND.equals(action))) {
            try (InputStream is = getContentResolver().openInputStream(uri)) {
                if (is != null) {
                    byte[] bytes = is.readAllBytes();
                    pendingBase64 = Base64.encodeToString(bytes, Base64.NO_WRAP);

                    // Detect MIME type
                    String mime = getContentResolver().getType(uri);
                    if (mime == null) {
                        String ext = MimeTypeMap.getFileExtensionFromUrl(uri.toString());
                        mime = MimeTypeMap.getSingleton().getMimeTypeFromExtension(ext);
                    }
                    pendingMimeType = mime != null ? mime : "application/octet-stream";
                }
            } catch (Exception e) {
                // Ignore — user will upload manually
            }
        }
    }

    public String consumePendingBase64() {
        String b = pendingBase64;
        pendingBase64 = "";
        return b;
    }

    public String consumePendingMimeType() {
        String m = pendingMimeType;
        pendingMimeType = "";
        return m;
    }
}
