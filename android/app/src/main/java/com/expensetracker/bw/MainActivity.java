package com.expensetracker.bw;

import android.os.Bundle;
import android.webkit.WebSettings;
import com.getcapacitor.BridgeActivity;

public class MainActivity extends BridgeActivity {
    @Override
    public void onCreate(Bundle savedInstanceState) {
        registerPlugin(SmsPlugin.class);
        super.onCreate(savedInstanceState);

        // Remove the "wv" WebView marker from the User-Agent.
        // Google blocks OAuth in views it identifies as embedded WebViews.
        // Stripping "wv" makes the WebView appear as a normal Chrome browser.
        WebSettings settings = getBridge().getWebView().getSettings();
        String ua = settings.getUserAgentString();
        String cleanUA = ua.replace("; wv", "").replace(" wv", "");
        settings.setUserAgentString(cleanUA);
    }
}
