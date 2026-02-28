package com.deliveryapp.driver;

import com.getcapacitor.BridgeActivity;
import android.os.Bundle;
import com.capacitorcommunity.texttospeech.TextToSpeech;

public class MainActivity extends BridgeActivity {
    @Override
    public void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        registerPlugin(TextToSpeech.class);
    }
}
