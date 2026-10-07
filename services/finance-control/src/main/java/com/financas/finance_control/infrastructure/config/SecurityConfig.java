package com.financas.finance_control.infrastructure.config;

import org.springframework.context.annotation.Bean;
import org.springframework.context.annotation.Configuration;
import org.springframework.security.config.annotation.web.builders.HttpSecurity;
import org.springframework.security.web.SecurityFilterChain;

import com.financas.finance_control.domain.service.JwtFilter;

@Configuration
public class SecurityConfig {

    private final JwtFilter jwtFilter;

    
}
