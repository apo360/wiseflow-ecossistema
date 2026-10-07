package com.financas.finance_control.application.controller;

import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RestController;

import jakarta.servlet.http.HttpServletRequest;
import java.util.Map;

@RestController
public class DashboardController {

    @GetMapping("/api/v1/dashboard")
    public Map<String, Object> dashboard(HttpServletRequest request) {

        String email = (String) request.getAttribute("user_email");

        return Map.of(
            "user", email,
            "balance", 250000,
            "status", "activo"
        );
    }
}
