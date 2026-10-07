package com.financas.finance_control.domain.model;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;
import java.math.BigDecimal;
import java.time.LocalDate;

@Entity
@Table(name = "income_history")
@Getter @Setter
public class IncomeHistory extends BaseEntity {

    @ManyToOne
    @JoinColumn(name = "income_source_id", nullable = false)
    private IncomeSource incomeSource;

    private BigDecimal previousAmount;
    private BigDecimal newAmount;

    private LocalDate effectiveDate;

    @Column(name = "user_id")
    private Long userId;
}
