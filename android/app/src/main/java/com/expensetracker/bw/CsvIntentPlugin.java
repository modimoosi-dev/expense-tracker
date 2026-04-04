package com.expensetracker.bw;

import com.getcapacitor.JSObject;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;

@CapacitorPlugin(name = "CsvIntent")
public class CsvIntentPlugin extends Plugin {

    @PluginMethod
    public void getPendingFile(PluginCall call) {
        MainActivity activity = (MainActivity) getActivity();
        JSObject result = new JSObject();
        result.put("base64",   activity.consumePendingBase64());
        result.put("mimeType", activity.consumePendingMimeType());
        call.resolve(result);
    }
}
