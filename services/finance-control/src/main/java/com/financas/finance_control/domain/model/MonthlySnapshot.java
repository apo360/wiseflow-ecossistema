package com.financas.finance_control.domain.model;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;
import java.math.BigDecimal;

@Entity
@Table(name = "monthly_snapshots",
       uniqueConstraints = @UniqueConstraint(columnNames = {"tenant_id", "year", "month"}))
@Getter @Setter
public class MonthlySnapshot extends BaseEntity {

    private Integer year;
    private Integer month;

    private BigDecimal totalIncome;
    private BigDecimal totalExpenses;
    private BigDecimal finalBalance;
}
