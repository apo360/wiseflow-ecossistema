package com.financas.finance_control.domain.model;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;

@Entity
@Table(name = "financial_accounts")
@Getter @Setter
public class FinancialAccount extends BaseEntity {

    @Column(nullable = false)
    private String bankName;

    private String accountName;

    @Column(nullable = false)
    private String currency;

    private Boolean isPrimary = false;

    @Column(name = "user_id")
    private Long userId;
}
