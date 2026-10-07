package com.financas.finance_control.domain.model;

import jakarta.persistence.*;
import lombok.*;
import java.math.BigDecimal;
import java.time.LocalDate;

import com.financas.finance_control.shared.enums.Frequency;

@Entity
@Table(name = "income_sources")
@Getter @Setter
public class IncomeSource extends BaseEntity {

    @Column(nullable = false)
    private String name;

    private String description;

    @Column(nullable = false)
    private BigDecimal amount;

    private String currency;

    @Enumerated(EnumType.STRING)
    private Frequency frequency;

    private LocalDate startDate;
    private LocalDate endDate;

    private Boolean isActive = true;
    private Boolean isPrimary = false;

    @ManyToOne
    @JoinColumn(name = "financial_account_id")
    private FinancialAccount account;

    @Column(name = "user_id")
    private Long userId;
}
