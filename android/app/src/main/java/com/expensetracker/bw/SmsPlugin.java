package com.expensetracker.bw;

import android.Manifest;
import android.database.Cursor;
import android.net.Uri;

import com.getcapacitor.JSArray;
import com.getcapacitor.JSObject;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;
import com.getcapacitor.annotation.Permission;
import com.getcapacitor.annotation.PermissionCallback;

@CapacitorPlugin(
    name = "SmsPlugin",
    permissions = {
        @Permission(strings = { Manifest.permission.READ_SMS }, alias = "sms")
    }
)
public class SmsPlugin extends Plugin {

    @PluginMethod
    public void getSmsInbox(PluginCall call) {
        if (!hasRequiredPermissions()) {
            requestAllPermissions(call, "onPermissionResult");
            return;
        }
        readSms(call);
    }

    @PermissionCallback
    private void onPermissionResult(PluginCall call) {
        if (hasRequiredPermissions()) {
            readSms(call);
        } else {
            call.reject("SMS permission denied");
        }
    }

    private void readSms(PluginCall call) {
        try {
            int limit = call.getInt("limit", 150);

            Uri inboxUri = Uri.parse("content://sms/inbox");
            Cursor cursor = getContext().getContentResolver().query(
                inboxUri,
                new String[]{"_id", "address", "body", "date"},
                null,
                null,
                "date DESC LIMIT " + limit
            );

            JSArray messages = new JSArray();
            if (cursor != null) {
                while (cursor.moveToNext()) {
                    String body = cursor.getString(cursor.getColumnIndexOrThrow("body"));
                    if (body == null) continue;

                    // Only include SMS that look like financial transactions
                    String lower = body.toLowerCase();
                    if (!lower.matches(".*\\b(received|sent|paid|payment|debit|credit|transfer|withdrawn|deposited|purchase|transaction|balance|amount|bwp|pula|usd|eur|zar|bank|airtime|data|momo|mpesa|orange money|smega|myzeap)\\b.*")) {
                        continue;
                    }

                    JSObject msg = new JSObject();
                    msg.put("id", cursor.getString(cursor.getColumnIndexOrThrow("_id")));
                    msg.put("address", cursor.getString(cursor.getColumnIndexOrThrow("address")));
                    msg.put("body", body.trim());
                    msg.put("date", cursor.getLong(cursor.getColumnIndexOrThrow("date")));
                    messages.put(msg);
                }
                cursor.close();
            }

            JSObject result = new JSObject();
            result.put("messages", messages);
            call.resolve(result);
        } catch (Exception e) {
            call.reject("Failed to read SMS: " + e.getMessage());
        }
    }
}
