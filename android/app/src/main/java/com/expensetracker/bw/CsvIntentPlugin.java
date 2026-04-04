package com.expensetracker.bw;

import com.getcapacitor.JSObject;
import com.getcapacitor.Plugin;
import com.getcapacitor.PluginCall;
import com.getcapacitor.PluginMethod;
import com.getcapacitor.annotation.CapacitorPlugin;

@CapacitorPlugin(name = "CsvIntent")
public class CsvIntentPlugin extends Plugin {

    @PluginMethod
    public void getPendingCsv(PluginCall call) {
        String csv = ((MainActivity) getActivity()).consumePendingCsv();
        JSObject result = new JSObject();
        result.put("csv", csv != null ? csv : "");
        call.resolve(result);
    }
}
