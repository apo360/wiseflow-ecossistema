package com.financas.finance_control.domain.model;

import jakarta.persistence.*;
import lombok.Getter;
import lombok.Setter;
import java.math.BigDecimal;
import java.time.LocalDate;

import com.financas.finance_control.shared.enums.GoalPriority;
import com.financas.finance_control.shared.enums.GoalStatus;

@Entity
@Table(name = "financial_goals")
@Getter @Setter
public class FinancialGoal extends BaseEntity {

    @Column(nullable = false)
    private String name;

    private String description;

    private BigDecimal targetAmount;
    private BigDecimal currentAmount;

    @Enumerated(EnumType.STRING)
    private GoalPriority priority;

    @Enumerated(EnumType.STRING)
    private GoalStatus status;

    private LocalDate startDate;
    private LocalDate targetDate;
}
