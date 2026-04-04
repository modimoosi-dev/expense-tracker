package com.expensetracker.bw;

import android.content.Intent;
import android.net.Uri;
import android.os.Bundle;
import android.webkit.WebSettings;

import com.getcapacitor.BridgeActivity;

import java.io.InputStream;
import java.nio.charset.StandardCharsets;

public class MainActivity extends BridgeActivity {

    private String pendingCsv = null;

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
                    pendingCsv = new String(bytes, StandardCharsets.UTF_8);
                }
            } catch (Exception e) {
                // Ignore — user will upload manually
            }
        }
    }

    public String consumePendingCsv() {
        String csv = pendingCsv;
        pendingCsv = null;
        return csv;
    }
}
