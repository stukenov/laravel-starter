# AI agents were moved out of the trunk because the scheme is unproven

The direction was worked through completely: engines, wrapper, security, data
model, screens. It still did not go into the main branch.

The owner's reason, in his words: the scheme has not been exercised by a
single live run from PHP. We designed it from the CLI help, which is not the
same as working code. The starter is the foundation of every future project,
and unproven things do not go into foundations.

Work continues in `feature/ai-agents` and merges only after live runs and
tests.

A useful side effect: the SSE mechanism **stayed in the trunk** and gets
exercised on the activity log. By the time agents arrive it will be proven
rather than raw alongside them. See [[sse-connections-live-25-seconds]].
